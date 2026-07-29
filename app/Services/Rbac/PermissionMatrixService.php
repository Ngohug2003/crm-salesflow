<?php

declare(strict_types=1);

namespace App\Services\Rbac;

use Spatie\Permission\Models\Role;
use Throwable;

final class PermissionMatrixService
{
    /**
     * Get complete permission matrix dataset including roles, module groups, permissions, and matrix lookup.
     *
     * @return array{
     *     roles: list<array{name: string, label: string, description: string, data_scope: string}>,
     *     modules: list<array{
     *         key: string,
     *         label: string,
     *         permissions: list<array{name: string, label: string, roles: array<string, bool>}>
     *     }>
     * }
     */
    public function getMatrix(): array
    {
        $roles = $this->getRolesDefinition();
        $modules = $this->getModulesDefinition();

        // Build active permission map per role from DB if roles exist
        $rolePermissionsMap = [];
        foreach ($roles as $roleDef) {
            $rolePermissionsMap[$roleDef['name']] = [];
            try {
                $roleModel = Role::findByName($roleDef['name'], 'web');
                /** @var list<string> $permNames */
                $permNames = $roleModel->permissions()->pluck('name')->all();
                foreach ($permNames as $pName) {
                    $rolePermissionsMap[$roleDef['name']][$pName] = true;
                }
            } catch (Throwable) {
                // Fallback if role does not exist in DB
            }
        }

        // Hydrate roles matrix mapping for each permission
        foreach ($modules as $mIndex => $module) {
            foreach ($module['permissions'] as $pIndex => $perm) {
                $pName = $perm['name'];
                $matrix = [];
                foreach ($roles as $roleDef) {
                    $rName = $roleDef['name'];
                    // Super admin has all permissions
                    if ($rName === (string) config('crm.rbac.super_admin_role', 'super-admin')) {
                        $matrix[$rName] = true;
                    } else {
                        $matrix[$rName] = $rolePermissionsMap[$rName][$pName] ?? $this->defaultPermissionCheck($rName, $pName);
                    }
                }
                $modules[$mIndex]['permissions'][$pIndex]['roles'] = $matrix;
            }
        }

        return [
            'roles' => $roles,
            'modules' => $modules,
        ];
    }

    /**
     * Get 5 core system roles with human-readable labels and Data Scope rules.
     *
     * @return list<array{name: string, label: string, description: string, data_scope: string}>
     */
    public function getRolesDefinition(): array
    {
        /** @var array<string, array{label: string, description: string, data_scope: string}> $definitions */
        $definitions = config('crm.rbac.roles', []);
        $scopeLabels = [
            'all' => 'Toàn bộ dữ liệu (All)',
            'department' => 'Phạm vi phòng ban (Department)',
            'owned' => 'Dữ liệu sở hữu (Owned)',
            'read-only' => 'Chỉ đọc trong phạm vi được cấp (Read-only)',
        ];

        return array_map(
            static fn (array $definition, string $name): array => [
                'name' => $name,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'data_scope' => $scopeLabels[$definition['data_scope']] ?? $definition['data_scope'],
            ],
            $definitions,
            array_keys($definitions),
        );
    }

    /**
     * Get permissions catalog grouped by 7 modules.
     *
     * @return list<array{key: string, label: string, permissions: list<array{name: string, label: string, roles: array<string, bool>}>}>
     */
    public function getModulesDefinition(): array
    {
        /** @var array<string, array{label: string, permissions: array<string, string>}> $groups */
        $groups = config('crm.rbac.permission_groups', []);

        return array_map(
            static fn (array $group, string $key): array => [
                'key' => $key,
                'label' => $group['label'],
                'permissions' => array_map(
                    static fn (string $label, string $name): array => [
                        'name' => $name,
                        'label' => $label,
                        'roles' => [],
                    ],
                    $group['permissions'],
                    array_keys($group['permissions']),
                ),
            ],
            $groups,
            array_keys($groups),
        );
    }

    /**
     * Fallback mapping check based on RBAC Catalog definitions.
     */
    private function defaultPermissionCheck(string $role, string $permission): bool
    {
        /** @var array{permissions?: list<string>} $definition */
        $definition = config("crm.rbac.roles.{$role}", []);

        return in_array($permission, $definition['permissions'] ?? [], true);
    }
}
