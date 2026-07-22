<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\SystemAuditService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(private readonly SystemAuditService $audit) {}

    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ])->validateWithBag('updateProfileInformation');

        $old = [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at === null ? null : (string) $user->email_verified_at,
        ];

        if ($input['email'] !== $user->email) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }

        $user->refresh();
        $new = [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at === null ? null : (string) $user->email_verified_at,
        ];

        if ($old !== $new) {
            $this->audit->record($user, $user, 'updated', 'Cập nhật hồ sơ cá nhân', $old, $new);
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
