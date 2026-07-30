<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DataScope;
use App\Exceptions\UserOperationException;
use App\Models\User;
use App\Repositories\Contracts\DepartmentRepository;
use App\Repositories\Contracts\SessionRepository;
use App\Repositories\Contracts\UserRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class UserManagementService
{
    public function __construct(
        private UserRepository $users,
        private DepartmentRepository $departments,
        private DataScopeService $dataScope,
        private SystemAuditService $audit,
        private SessionRepository $sessions,
    ) {}

    /** @param array{name: string, email: string, department_id: ?int, is_active: bool, roles: list<string>, password?: string} $attributes */
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

        $roles = array_values(array_unique($attributes['roles']));
        $this->ensureKnownRoles($roles);
        $this->ensureSuperAdminAssignmentIsAllowed($actor, $user, $roles);
        $this->ensureItMembershipIsAllowed($actor, $user, $attributes['department_id']);
        unset($attributes['roles']);

        return DB::transaction(function () use ($actor, $user, $attributes, $roles): User {
            /** @var list<string> $administratorRoles */
            $administratorRoles = config('crm.rbac.administrator_roles', ['super-admin', 'admin']);
            $activeAdministratorIds = $this->users->lockActiveAdministratorIds($administratorRoles);
            $oldValues = $user === null ? null : $this->auditSnapshot($user);
            $willRemainAdministrator = $attributes['is_active']
                && array_intersect($roles, $administratorRoles) !== [];

            if ($user !== null
                && in_array($user->getKey(), $activeAdministratorIds, true)
                && ! $willRemainAdministrator
                && count(array_diff($activeAdministratorIds, [$user->getKey()])) === 0) {
                throw new UserOperationException(
                    'roles',
                    'Không thể khóa hoặc hạ quyền quản trị viên đang hoạt động cuối cùng.',
                );
            }

            $passwordChanged = array_key_exists('password', $attributes);

            $revokedSessionsCount = 0;
            if ($user !== null) {
                $isBlocking = $user->is_active && ! $attributes['is_active'];
                if ($isBlocking) {
                    $revokedSessionsCount = $this->sessions->deleteAllSessions($user);
                } elseif ($passwordChanged) {
                    $user->forceFill(['remember_token' => Str::random(60)])->save();
                    if ($actor->getKey() === $user->getKey()) {
                        $currentSessionId = request()->hasSession() ? request()->session()->getId() : 'fake-id';
                        $revokedSessionsCount = $this->sessions->deleteOtherSessions($user, $currentSessionId);
                    } else {
                        $revokedSessionsCount = $this->sessions->deleteAllSessions($user);
                    }
                }
            }

            if ($user === null) {
                $attributes['email_verified_at'] = now();
                $savedUser = $this->users->create($attributes);
            } else {
                $savedUser = $this->users->update($user, $attributes);
            }

            $savedUser = $this->users->syncRoles($savedUser, $roles);
            $newValues = $this->auditSnapshot($savedUser);

            if ($oldValues !== $newValues || $passwordChanged) {
                $this->audit->record(
                    $actor,
                    $savedUser,
                    $user === null ? 'created' : 'updated',
                    $user === null ? 'Tạo người dùng' : 'Cập nhật người dùng',
                    $oldValues,
                    $newValues,
                    [
                        'password_changed' => $passwordChanged,
                        'revoked_sessions_count' => $revokedSessionsCount,
                    ],
                );
            }

            return $savedUser;
        });
    }

    /** @param list<string> $roles */
    private function ensureKnownRoles(array $roles): void
    {
        $knownRoles = array_keys((array) config('crm.rbac.roles', []));

        if ($roles === [] || array_diff($roles, $knownRoles) !== []) {
            throw new UserOperationException('roles', 'Danh sách vai trò không hợp lệ.');
        }
    }

    /** @param list<string> $roles */
    private function ensureSuperAdminAssignmentIsAllowed(User $actor, ?User $user, array $roles): void
    {
        $superAdminRole = (string) config('crm.rbac.super_admin_role');

        if (! $actor->hasRole($superAdminRole)
            && (in_array($superAdminRole, $roles, true) || $user?->hasRole($superAdminRole))) {
            throw new AuthorizationException('Chỉ Super Admin được quản lý role Super Admin.');
        }
    }

    private function ensureItMembershipIsAllowed(User $actor, ?User $user, ?int $newDepartmentId): void
    {
        $superAdminRole = (string) config('crm.rbac.super_admin_role');

        if (! $actor->hasRole($superAdminRole)
            && ($this->departments->hasCode($user?->department_id, 'IT')
                || $this->departments->hasCode($newDepartmentId, 'IT'))) {
            throw new AuthorizationException('Chỉ Super Admin được quản lý thành viên phòng IT.');
        }
    }

    /** @return array{name: string, email: string, department_id: ?int, is_active: bool, roles: list<string>} */
    private function auditSnapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'department_id' => $user->department_id,
            'is_active' => $user->is_active,
            'roles' => $user->getRoleNames()->sort()->values()->all(),
        ];
    }
}
