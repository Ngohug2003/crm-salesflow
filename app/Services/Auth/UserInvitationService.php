<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\UserOperationException;
use App\Mail\UserInvitationMail;
use App\Models\Staff;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\StaffService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class UserInvitationService
{
    /**
     * Create a new user invitation and send invitation email.
     *
     * @param  array{name: string, email: string, department_id: ?int, role: string}  $data
     */
    public function createInvitation(User $actor, array $data): UserInvitation
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $normalizedEmail = mb_strtolower(trim($data['email']));

        if (User::query()->where('email', $normalizedEmail)->exists()) {
            throw new UserOperationException('email', 'Email này đã được sử dụng cho một tài khoản người dùng trong hệ thống.');
        }

        // Revoke any existing active invitations for this email
        UserInvitation::query()
            ->where('email', $normalizedEmail)
            ->whereNull('accepted_at')
            ->delete();

        /** @var UserInvitation $invitation */
        $invitation = UserInvitation::query()->create([
            'email' => $normalizedEmail,
            'name' => trim($data['name']),
            'department_id' => $data['department_id'] ?: null,
            'role' => $data['role'] ?: 'sales',
            'token' => Str::random(64),
            'expires_at' => Carbon::now()->addDays(7),
            'invited_by' => $actor->id,
        ]);

        // Sync or create Staff record so Staff and User modules stay consistent
        try {
            $existingStaff = Staff::query()->where('email', $normalizedEmail)->first();
            if ($existingStaff === null) {
                app(StaffService::class)->saveStaff([
                    'full_name' => trim($data['name']),
                    'email' => $normalizedEmail,
                    'department_id' => $data['department_id'] ?: null,
                    'position' => 'Staff (Đã mời)',
                    'is_active' => true,
                ]);
            } else {
                $existingStaff->update([
                    'full_name' => trim($data['name']),
                    'department_id' => $data['department_id'] ?: $existingStaff->department_id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Could not sync staff profile during user invitation: ' . $e->getMessage(), ['exception' => $e]);
        }

        try {
            Mail::to($invitation->email)->send(new UserInvitationMail($invitation));
        } catch (\Throwable $e) {
            Log::warning('Could not send invitation email via SMTP: ' . $e->getMessage(), ['exception' => $e]);
        }

        return $invitation;
    }

    /**
     * Resend active invitation email.
     */
    public function resendInvitation(User $actor, UserInvitation $invitation): void
    {
        Gate::forUser($actor)->authorize('create', User::class);

        if ($invitation->isAccepted()) {
            throw new UserOperationException('invitation', 'Lời mời này đã được chấp nhận trước đó.');
        }

        // Renew expiration date & token if expired
        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        try {
            Mail::to($invitation->email)->send(new UserInvitationMail($invitation));
        } catch (\Throwable $e) {
            Log::warning('Could not resend invitation email via SMTP: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Revoke / Cancel a pending invitation.
     */
    public function revokeInvitation(User $actor, UserInvitation $invitation): void
    {
        Gate::forUser($actor)->authorize('delete', $actor);

        if ($invitation->isAccepted()) {
            throw new UserOperationException('invitation', 'Không thể hủy lời mời đã được chấp nhận.');
        }

        $invitation->delete();
    }

    /**
     * Accept invitation and register new User account.
     */
    public function acceptInvitation(string $token, string $password): User
    {
        /** @var UserInvitation|null $invitation */
        $invitation = UserInvitation::query()
            ->where('token', $token)
            ->first();

        if ($invitation === null) {
            throw new UserOperationException('token', 'Liên kết lời mời không tồn tại hoặc đã bị hủy.');
        }

        if ($invitation->isAccepted()) {
            throw new UserOperationException('token', 'Lời mời này đã được sử dụng để kích hoạt tài khoản.');
        }

        if ($invitation->isExpired()) {
            throw new UserOperationException('token', 'Liên kết lời mời đã hết hạn (hạn sử dụng 7 ngày). Vui lòng yêu cầu Admin gửi lại lời mời mới.');
        }

        return DB::transaction(function () use ($invitation, $password): User {
            /** @var User $user */
            $user = User::query()->create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'password' => Hash::make($password),
                'department_id' => $invitation->department_id,
                'email_verified_at' => Carbon::now(),
            ]);

            $user->assignRole($invitation->role);

            $invitation->update([
                'accepted_at' => Carbon::now(),
            ]);

            Staff::query()->where('email', $user->email)->update([
                'user_id' => $user->id,
            ]);

            Auth::login($user);

            return $user;
        });
    }
}
