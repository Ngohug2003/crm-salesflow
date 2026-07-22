<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\SystemAuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private readonly SystemAuditService $audit) {}

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
        ])->save();

        $this->audit->record(
            $user,
            $user,
            'password-reset',
            'Đặt lại mật khẩu qua luồng quên mật khẩu',
            null,
            null,
            ['password_changed' => true],
        );
    }
}
