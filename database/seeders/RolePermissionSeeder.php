<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = (string) config('crm.rbac.guard', 'web');
        $permissions = $this->permissionNames();
        $roles = $this->roles();

        $this->validateCatalog($permissions, $roles);

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        foreach ($roles as $name => $definition) {
            $role = Role::findOrCreate($name, $guard);
            $isCustomized = Schema::hasTable('role_permission_customizations')
                && DB::table('role_permission_customizations')
                    ->where('role_id', $role->getKey())
                    ->exists();

            if (! $isCustomized) {
                $role->syncPermissions($definition['permissions']);
            }
        }

        $registrar->forgetCachedPermissions();
    }

    /** @return list<string> */
    private function permissionNames(): array
    {
        /** @var array<string, array{label: string, permissions: array<string, string>}> $groups */
        $groups = config('crm.rbac.permission_groups', []);
        $permissions = [];

        foreach ($groups as $group) {
            $permissions = [...$permissions, ...array_keys($group['permissions'])];
        }

        return $permissions;
    }

    /** @return array<string, array{label: string, description: string, data_scope: string, permissions: list<string>}> */
    private function roles(): array
    {
        /** @var array<string, array{label: string, description: string, data_scope: string, permissions: list<string>}> $roles */
        $roles = config('crm.rbac.roles', []);

        return $roles;
    }

    /**
     * @param  list<string>  $permissions
     * @param  array<string, array{label: string, description: string, data_scope: string, permissions: list<string>}>  $roles
     */
    private function validateCatalog(array $permissions, array $roles): void
    {
        if ($permissions === [] || count($permissions) !== count(array_unique($permissions))) {
            throw new LogicException('CRM permission catalog must contain unique permission names.');
        }

        $superAdminRole = (string) config('crm.rbac.super_admin_role');

        if (! array_key_exists($superAdminRole, $roles)) {
            throw new LogicException('The configured super-admin role is missing from the CRM role catalog.');
        }

        /** @var list<string> $protectedPermissions */
        $protectedPermissions = config('crm.rbac.protected_role_permissions', []);
        $unknownProtectedPermissions = array_diff($protectedPermissions, $permissions);

        if ($unknownProtectedPermissions !== []) {
            throw new LogicException('Protected role permissions reference unknown permissions: '.implode(', ', $unknownProtectedPermissions));
        }

        /** @var list<string> $dataScopes */
        $dataScopes = config('crm.rbac.data_scopes', []);

        foreach ($roles as $name => $definition) {
            $unknownPermissions = array_diff($definition['permissions'], $permissions);

            if ($unknownPermissions !== []) {
                throw new LogicException("Role [{$name}] references unknown permissions: ".implode(', ', $unknownPermissions));
            }

            if (! in_array($definition['data_scope'], $dataScopes, true)) {
                throw new LogicException("Role [{$name}] has an invalid data scope [{$definition['data_scope']}].");
            }
        }
    }
}
