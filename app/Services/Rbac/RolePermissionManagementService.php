<?php

declare(strict_types=1);

namespace App\Services\Rbac;

use App\Models\RolePermissionCustomization;
use App\Models\User;
use App\Services\SystemAuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class RolePermissionManagementService
{
    public function __construct(
        private SystemAuditService $audit,
        private PermissionRegistrar $permissionRegistrar,
    ) {}

    /** @return list<string> */
    public function permissionsForRole(string $roleName): array
    {
        $this->assertKnownRole($roleName);

        if ($roleName === (string) config('crm.rbac.super_admin_role', 'super-admin')) {
            return $this->catalogPermissions();
        }

        /** @var list<string> $permissions */
        $permissions = Role::findByName($roleName, $this->guard())
            ->permissions()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return $permissions;
    }

    /** @return list<string> */
    public function editableRoleNames(User $actor): array
    {
        Gate::forUser($actor)->authorize('roles.manage');

        return array_values(array_filter(
            array_keys($this->roleDefinitions()),
            fn (string $roleName): bool => $this->canEditRole($actor, $roleName),
        ));
    }

    public function canEditRole(User $actor, string $roleName): bool
    {
        if (! array_key_exists($roleName, $this->roleDefinitions()) || ! $actor->can('roles.manage')) {
            return false;
        }

        $actorRank = $this->actorRank($actor);
        $targetRank = array_search($roleName, array_keys($this->roleDefinitions()), true);

        return $actorRank !== null
            && is_int($targetRank)
            && $targetRank > $actorRank;
    }

    public function canTogglePermission(User $actor, string $roleName, string $permission): bool
    {
        if (! $this->canEditRole($actor, $roleName) || ! in_array($permission, $this->catalogPermissions(), true)) {
            return false;
        }

        if ($this->isSuperAdmin($actor)) {
            return true;
        }

        return ! in_array($permission, $this->protectedPermissions(), true)
            && $actor->can($permission);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function updateRolePermissions(User $actor, string $roleName, array $permissions): bool
    {
        $this->authorizeRoleMutation($actor, $roleName);
        $permissions = $this->normalizePermissions($permissions);

        /** @var array{role: Role, old: list<string>, new: list<string>, changed: bool} $result */
        $result = DB::transaction(function () use ($actor, $roleName, $permissions): array {
            /** @var Role $role */
            $role = Role::query()
                ->where('guard_name', $this->guard())
                ->where('name', $roleName)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var list<string> $oldPermissions */
            $oldPermissions = $role->permissions()->pluck('name')->sort()->values()->all();
            $this->authorizePermissionDelta($actor, $roleName, $oldPermissions, $permissions);

            if ($oldPermissions === $permissions) {
                return [
                    'role' => $role,
                    'old' => $oldPermissions,
                    'new' => $permissions,
                    'changed' => false,
                ];
            }

            $role->syncPermissions($permissions);
            RolePermissionCustomization::query()->updateOrCreate(
                ['role_id' => $role->getKey()],
                ['customized_by' => $actor->getKey()],
            );

            return [
                'role' => $role,
                'old' => $oldPermissions,
                'new' => $permissions,
                'changed' => true,
            ];
        });

        $this->permissionRegistrar->forgetCachedPermissions();

        if (! $result['changed']) {
            return false;
        }

        $this->audit->record(
            $actor,
            $result['role'],
            'role.permissions.updated',
            "Cập nhật quyền cho vai trò {$roleName}",
            ['permissions' => $result['old']],
            ['permissions' => $result['new']],
            [
                'module' => 'roles',
                'role_name' => $roleName,
                'added_permissions' => array_values(array_diff($result['new'], $result['old'])),
                'removed_permissions' => array_values(array_diff($result['old'], $result['new'])),
            ],
        );

        return true;
    }

    public function resetRolePermissions(User $actor, string $roleName): bool
    {
        $this->authorizeRoleMutation($actor, $roleName);

        /** @var list<string> $defaults */
        $defaults = $this->roleDefinitions()[$roleName]['permissions'];
        $changed = $this->updateRolePermissions($actor, $roleName, $defaults);

        $role = Role::findByName($roleName, $this->guard());
        RolePermissionCustomization::query()->where('role_id', $role->getKey())->delete();

        return $changed;
    }

    private function authorizeRoleMutation(User $actor, string $roleName): void
    {
        Gate::forUser($actor)->authorize('roles.manage');
        $this->assertKnownRole($roleName);

        if (! $this->canEditRole($actor, $roleName)) {
            throw new AuthorizationException('Bạn không được phép chỉnh sửa vai trò này.');
        }
    }

    /**
     * @param  list<string>  $oldPermissions
     * @param  list<string>  $newPermissions
     */
    private function authorizePermissionDelta(
        User $actor,
        string $roleName,
        array $oldPermissions,
        array $newPermissions,
    ): void {
        $changedPermissions = array_unique([
            ...array_diff($oldPermissions, $newPermissions),
            ...array_diff($newPermissions, $oldPermissions),
        ]);

        foreach ($changedPermissions as $permission) {
            if (! $this->canTogglePermission($actor, $roleName, $permission)) {
                throw new AuthorizationException("Bạn không được phép thay đổi quyền [{$permission}].");
            }
        }
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private function normalizePermissions(array $permissions): array
    {
        $permissions = array_values(array_unique($permissions));
        $unknown = array_diff($permissions, $this->catalogPermissions());

        if ($unknown !== []) {
            throw new InvalidArgumentException('Danh sách chứa Permission không thuộc catalog CRM.');
        }

        sort($permissions);

        return $permissions;
    }

    private function assertKnownRole(string $roleName): void
    {
        if (! array_key_exists($roleName, $this->roleDefinitions())) {
            throw new InvalidArgumentException("Vai trò [{$roleName}] không thuộc catalog CRM.");
        }
    }

    private function actorRank(User $actor): ?int
    {
        $roleNames = array_keys($this->roleDefinitions());
        $ranks = [];

        foreach ($actor->getRoleNames() as $roleName) {
            $rank = array_search((string) $roleName, $roleNames, true);
            if (is_int($rank)) {
                $ranks[] = $rank;
            }
        }

        return $ranks === [] ? null : min($ranks);
    }

    private function isSuperAdmin(User $actor): bool
    {
        return $actor->hasRole((string) config('crm.rbac.super_admin_role', 'super-admin'));
    }

    /** @return list<string> */
    private function catalogPermissions(): array
    {
        /** @var array<string, array{permissions: array<string, string>}> $groups */
        $groups = config('crm.rbac.permission_groups', []);

        return array_merge(...array_map(
            static fn (array $group): array => array_keys($group['permissions']),
            array_values($groups),
        ));
    }

    /** @return list<string> */
    private function protectedPermissions(): array
    {
        /** @var list<string> $permissions */
        $permissions = config('crm.rbac.protected_role_permissions', []);

        return $permissions;
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     data_scope: string,
     *     permissions: list<string>
     * }>
     */
    private function roleDefinitions(): array
    {
        /** @var array<string, array{label: string, description: string, data_scope: string, permissions: list<string>}> $roles */
        $roles = config('crm.rbac.roles', []);

        return $roles;
    }

    private function guard(): string
    {
        return (string) config('crm.rbac.guard', 'web');
    }
}
