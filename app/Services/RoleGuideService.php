<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DataScope;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class RoleGuideService
{
    public function __construct(private DataScopeService $dataScope) {}

    /**
     * @return array{
     *     roles: list<array{name: string, label: string}>,
     *     scope: string,
     *     permission_count: int,
     *     is_super_admin: bool
     * }
     */
    public function currentUserSummary(User $user): array
    {
        $roles = $this->roles();
        $assignedRoles = $user->getRoleNames()
            ->map(fn (string $roleName): array => [
                'name' => $roleName,
                'label' => $roles[$roleName]['label'] ?? $roleName,
            ])
            ->values()
            ->all();
        $isSuperAdmin = $user->hasRole((string) config('crm.rbac.super_admin_role'));

        return [
            'roles' => $assignedRoles,
            'scope' => $this->scopeLabel($this->dataScope->resolve($user)),
            'permission_count' => $isSuperAdmin
                ? count($this->permissionLabels())
                : $user->getAllPermissions()->count(),
            'is_super_admin' => $isSuperAdmin,
        ];
    }

    /**
     * @return list<array{
     *     name: string,
     *     label: string,
     *     description: string,
     *     scope: string,
     *     permission_count: int,
     *     is_super_admin: bool,
     *     groups: list<array{label: string, permissions: list<string>}>
     * }>
     */
    public function catalog(): array
    {
        $superAdminRole = (string) config('crm.rbac.super_admin_role');
        $groups = $this->permissionGroups();
        $catalog = [];

        foreach ($this->roles() as $name => $definition) {
            $isSuperAdmin = $name === $superAdminRole;
            $permissionNames = $isSuperAdmin
                ? array_keys($this->permissionLabels())
                : $definition['permissions'];
            $permissionGroups = [];

            foreach ($groups as $group) {
                $permissions = [];

                foreach ($group['permissions'] as $permissionName => $permissionLabel) {
                    if (in_array($permissionName, $permissionNames, true)) {
                        $permissions[] = $permissionLabel;
                    }
                }

                if ($permissions !== []) {
                    $permissionGroups[] = [
                        'label' => $group['label'],
                        'permissions' => $permissions,
                    ];
                }
            }

            $catalog[] = [
                'name' => $name,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'scope' => $this->scopeLabel(DataScope::from($definition['data_scope'])),
                'permission_count' => count($permissionNames),
                'is_super_admin' => $isSuperAdmin,
                'groups' => $permissionGroups,
            ];
        }

        return $catalog;
    }

    /** @return array<string, array{label: string, description: string, data_scope: string, permissions: list<string>}> */
    private function roles(): array
    {
        /** @var array<string, array{label: string, description: string, data_scope: string, permissions: list<string>}> $roles */
        $roles = config('crm.rbac.roles', []);

        return $roles;
    }

    /** @return array<string, array{label: string, permissions: array<string, string>}> */
    private function permissionGroups(): array
    {
        /** @var array<string, array{label: string, permissions: array<string, string>}> $groups */
        $groups = config('crm.rbac.permission_groups', []);

        return $groups;
    }

    /** @return array<string, string> */
    private function permissionLabels(): array
    {
        $permissions = [];

        foreach ($this->permissionGroups() as $group) {
            $permissions = [...$permissions, ...$group['permissions']];
        }

        return $permissions;
    }

    private function scopeLabel(DataScope $scope): string
    {
        return match ($scope) {
            DataScope::All => 'Toàn bộ dữ liệu',
            DataScope::Department => 'Dữ liệu trong phòng ban',
            DataScope::Owned => 'Dữ liệu do chính mình phụ trách',
            DataScope::ReadOnly => 'Chỉ đọc dữ liệu được cấp quyền',
        };
    }
}
