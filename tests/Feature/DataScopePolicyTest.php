<?php

use App\Data\UserListFilters;
use App\Enums\DataScope;
use App\Livewire\Departments\DepartmentManagement;
use App\Models\Department;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\Authorization\DataScopeResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function userWithRole(string $role, ?Department $department = null): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('resolves the configured data scope and chooses the broadest assigned role', function (): void {
    $resolver = app(DataScopeResolver::class);

    $superAdmin = userWithRole('super-admin');
    $admin = userWithRole('admin');
    $manager = userWithRole('sales-manager');
    $sales = userWithRole('sales');
    $viewer = userWithRole('viewer');
    $sales->assignRole('sales-manager');

    expect($resolver->resolve($superAdmin))->toBe(DataScope::All)
        ->and($resolver->resolve($admin))->toBe(DataScope::All)
        ->and($resolver->resolve($manager))->toBe(DataScope::Department)
        ->and($resolver->resolve($sales))->toBe(DataScope::Department)
        ->and($resolver->resolve($viewer))->toBe(DataScope::ReadOnly);
});

it('enforces all department owned and read-only scopes in repository queries', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();

    $admin = userWithRole('admin', $departmentB);
    $manager = userWithRole('sales-manager', $departmentA);
    $sales = userWithRole('sales', $departmentA);
    $colleague = User::factory()->create(['department_id' => $departmentA->getKey()]);
    $outsider = User::factory()->create(['department_id' => $departmentB->getKey()]);
    $viewer = userWithRole('viewer', $departmentB);

    $targets = [$manager, $sales, $colleague, $outsider, $viewer];
    $targetIds = collect($targets)->pluck('id')->sort()->values()->all();
    $repository = app(UserRepository::class);

    $visibleIds = static fn (User $actor): array => collect($repository
        ->paginateVisibleTo($actor, new UserListFilters, 100)
        ->items())
        ->pluck('id')
        ->intersect($targetIds)
        ->sort()
        ->values()
        ->all();

    expect($visibleIds($admin))->toBe($targetIds)
        ->and($visibleIds($manager))->toBe(collect([$manager, $sales, $colleague])->pluck('id')->sort()->values()->all())
        ->and($visibleIds($sales))->toBe([$sales->getKey()])
        ->and($visibleIds($viewer))->toBe($targetIds);
});

it('combines user permissions with record scope in the user policy', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $manager = userWithRole('sales-manager', $departmentA);
    $sameDepartment = User::factory()->create(['department_id' => $departmentA->getKey()]);
    $otherDepartment = User::factory()->create(['department_id' => $departmentB->getKey()]);
    $admin = userWithRole('admin', $departmentA);
    $viewer = userWithRole('viewer', $departmentB);

    $manager->givePermissionTo('users.update');
    $viewer->givePermissionTo(['users.view', 'users.update']);

    expect(Gate::forUser($admin)->allows('update', $otherDepartment))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('view', $sameDepartment))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('view', $otherDepartment))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('update', $sameDepartment))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('update', $otherDepartment))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('view', $sameDepartment))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('update', $sameDepartment))->toBeFalse();
});

it('allows managers to read departments but rejects direct mutation actions', function (): void {
    $department = Department::factory()->create();
    $manager = userWithRole('sales-manager', $department);

    $this->actingAs($manager)
        ->get('/settings/departments')
        ->assertOk()
        ->assertSee('Phòng ban')
        ->assertDontSee('Tạo phòng ban')
        ->assertDontSee('Sửa')
        ->assertDontSee('openDelete(');

    Livewire::actingAs($manager)
        ->test(DepartmentManagement::class)
        ->call('openCreate')
        ->assertForbidden();

    Livewire::actingAs($manager)
        ->test(DepartmentManagement::class)
        ->call('toggleActive', $department->getKey())
        ->assertForbidden();

    expect($department->refresh()->is_active)->toBeTrue();
});

it('denies department pages to roles without a viewing permission', function (): void {
    $viewer = userWithRole('viewer');

    $this->actingAs($viewer)
        ->get('/settings/departments')
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Phòng ban');

    Livewire::actingAs($viewer)
        ->test(DepartmentManagement::class)
        ->assertForbidden();
});
