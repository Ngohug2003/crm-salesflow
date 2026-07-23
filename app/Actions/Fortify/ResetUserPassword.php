<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Repositories\Contracts\SessionRepository;
use App\Services\SystemAuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(
        private readonly SystemAuditService $audit,
        private readonly SessionRepository $sessions,
    ) {}

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $revokedCount = $this->sessions->deleteAllSessions($user);

        $this->audit->record(
            $user,
            $user,
            'password-reset',
            'Đặt lại mật khẩu qua luồng quên mật khẩu',
            null,
            null,
            [
                'password_changed' => true,
                'revoked_sessions_count' => $revokedCount,
            ],
        );
    }
}
