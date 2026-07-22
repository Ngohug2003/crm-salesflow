<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/** @return list<string> */
function crmPermissionNames(): array
{
    /** @var array<string, array{label: string, permissions: array<string, string>}> $groups */
    $groups = config('crm.rbac.permission_groups');

    return collect($groups)
        ->flatMap(static fn (array $group): array => array_keys($group['permissions']))
        ->values()
        ->all();
}

it('defines a valid CRM role and permission catalog', function (): void {
    $permissions = crmPermissionNames();

    /** @var array<string, array{label: string, description: string, data_scope: string, permissions: list<string>}> $roles */
    $roles = config('crm.rbac.roles');

    expect($permissions)
        ->toHaveCount(45)
        ->and(array_values(array_unique($permissions)))->toBe($permissions)
        ->and(array_keys($roles))->toBe(['super-admin', 'admin', 'sales-manager', 'sales', 'viewer'])
        ->and(array_column($roles, 'data_scope'))->toBe(['all', 'all', 'department', 'owned', 'read-only'])
        ->and($roles['super-admin']['permissions'])->toBe([])
        ->and($roles['admin']['permissions'])->toBe($permissions);

    foreach ($roles as $definition) {
        expect(array_diff($definition['permissions'], $permissions))->toBe([]);
    }
});

it('seeds permissions and role assignments idempotently', function (): void {
    $this->seed(RolePermissionSeeder::class);

    Role::findByName('admin')->revokePermissionTo('users.view');
    Role::findByName('viewer')->givePermissionTo('users.create');

    $this->seed(RolePermissionSeeder::class);

    expect(Permission::query()->count())->toBe(45)
        ->and(Role::query()->count())->toBe(5)
        ->and(Role::findByName('super-admin')->permissions)->toHaveCount(0)
        ->and(Role::findByName('admin')->permissions)->toHaveCount(45)
        ->and(Role::findByName('sales-manager')->permissions)->toHaveCount(39)
        ->and(Role::findByName('sales')->permissions)->toHaveCount(30)
        ->and(Role::findByName('viewer')->permissions)->toHaveCount(8)
        ->and(Role::findByName('admin')->hasPermissionTo('users.view'))->toBeTrue()
        ->and(Role::findByName('viewer')->hasPermissionTo('users.create'))->toBeFalse();
});

it('allows super admins to bypass gates without direct permissions', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    expect($superAdmin->getAllPermissions())->toHaveCount(0)
        ->and($superAdmin->can('users.delete'))->toBeTrue()
        ->and($superAdmin->can('unregistered.system-ability'))->toBeTrue()
        ->and($viewer->can('leads.view'))->toBeTrue()
        ->and($viewer->can('leads.create'))->toBeFalse();
});

it('assigns the super admin role to the seeded administrator idempotently', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

    expect($admin->hasRole('super-admin'))->toBeTrue()
        ->and($admin->roles)->toHaveCount(1)
        ->and(Role::query()->count())->toBe(5)
        ->and(Permission::query()->count())->toBe(45);
});
