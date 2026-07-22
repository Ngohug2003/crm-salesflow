<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DataScope;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class UserManagementService
{
    public function __construct(
        private UserRepository $users,
        private DataScopeService $dataScope,
    ) {}

    /** @param array{name: string, email: string, department_id: ?int, is_active: bool, password?: string} $attributes */
    public function save(User $actor, ?User $user, array $attributes): User
    {
        $scope = $this->dataScope->resolve($actor);

        if (
            in_array($scope, [DataScope::Department, DataScope::Owned], true)
            && $attributes['department_id'] !== $actor->department_id
        ) {
            throw new AuthorizationException('Bạn không thể chuyển người dùng ra ngoài phòng ban của mình.');
        }

        if ($user === null && $scope === DataScope::Owned) {
            throw new AuthorizationException('Phạm vi sở hữu không cho phép tạo tài khoản người dùng khác.');
        }

        if ($user === null) {
            $attributes['email_verified_at'] = now();

            return $this->users->create($attributes);
        }

        return $this->users->update($user, $attributes);
    }
}
