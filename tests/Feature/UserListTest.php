<?php

use App\Livewire\Users\UserList;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function listedUserWithRole(string $role, ?Department $department = null, array $attributes = []): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'email_verified_at' => now(),
        'is_active' => true,
        ...$attributes,
    ]);
    $user->assignRole($role);

    return $user;
}

it('protects the user list and shows its navigation to authorized users', function (): void {
    $admin = listedUserWithRole('admin');

    $this->get('/settings/users')->assertRedirect('/login');

    $this->actingAs($admin)
        ->get('/settings/users')
        ->assertOk()
        ->assertSee('Người dùng')
        ->assertSee('wire:navigate.hover', false);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Tài khoản người dùng');
});

it('searches and filters users by department role and status', function (): void {
    $salesDepartment = Department::factory()->create(['name' => 'Kinh doanh', 'code' => 'SALES']);
    $supportDepartment = Department::factory()->create(['name' => 'Hỗ trợ', 'code' => 'SUPPORT']);
    $admin = listedUserWithRole('admin', attributes: ['name' => 'System Admin']);
    $activeManager = listedUserWithRole('sales-manager', $salesDepartment, [
        'name' => 'Nguyễn An',
        'email' => 'an@salesflow.test',
    ]);
    $inactiveSales = listedUserWithRole('sales', $supportDepartment, [
        'name' => 'Trần Bình',
        'email' => 'binh@salesflow.test',
        'is_active' => false,
    ]);
    $unassigned = User::factory()->create([
        'name' => 'Lê Chi',
        'email' => 'chi@salesflow.test',
        'department_id' => null,
    ]);

    $component = Livewire::actingAs($admin)
        ->test(UserList::class)
        ->set('search', 'AN@SALESFLOW')
        ->assertSee($activeManager->email)
        ->assertDontSee($inactiveSales->email)
        ->set('search', '')
        ->set('department', (string) $supportDepartment->getKey())
        ->assertSee($inactiveSales->email)
        ->assertDontSee($activeManager->email)
        ->set('department', 'all')
        ->set('role', 'sales-manager')
        ->assertSee($activeManager->email)
        ->assertDontSee($inactiveSales->email)
        ->set('role', 'all')
        ->set('status', 'inactive')
        ->assertSee($inactiveSales->email)
        ->assertDontSee($activeManager->email)
        ->set('status', 'all')
        ->set('department', 'unassigned')
        ->assertSee($unassigned->email)
        ->assertDontSee($activeManager->email)
        ->call('clearFilters');

    $component
        ->assertSet('search', '')
        ->assertSet('department', 'all')
        ->assertSet('role', 'all')
        ->assertSet('status', 'all');
});

it('hydrates filter state from the url query string', function (): void {
    $department = Department::factory()->create();
    $admin = listedUserWithRole('admin');
    $matchingUser = listedUserWithRole('sales-manager', $department, [
        'name' => 'URL Target',
        'email' => 'url-target@salesflow.test',
        'is_active' => false,
    ]);

    Livewire::withQueryParams([
        'q' => 'url-target',
        'department' => (string) $department->getKey(),
        'role' => 'sales-manager',
        'status' => 'inactive',
    ])->actingAs($admin)
        ->test(UserList::class)
        ->assertSet('search', 'url-target')
        ->assertSet('department', (string) $department->getKey())
        ->assertSet('role', 'sales-manager')
        ->assertSet('status', 'inactive')
        ->assertSee($matchingUser->email);
});

it('paginates the scoped result and can move to the next page', function (): void {
    $admin = listedUserWithRole('admin', attributes: ['name' => 'ZZZ Admin']);

    foreach (range(1, 16) as $number) {
        User::factory()->create([
            'name' => sprintf('User %02d', $number),
            'email' => sprintf('user-%02d@salesflow.test', $number),
        ]);
    }

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->assertSee('user-01@salesflow.test')
        ->assertDontSee('user-16@salesflow.test')
        ->call('setPage', 2)
        ->assertSee('user-16@salesflow.test')
        ->assertDontSee('user-01@salesflow.test');
});

it('limits managers to users and department filters in their own department', function (): void {
    $departmentA = Department::factory()->create(['code' => 'DEPT-A']);
    $departmentB = Department::factory()->create(['code' => 'DEPT-B']);
    $manager = listedUserWithRole('sales-manager', $departmentA, ['email' => 'manager@salesflow.test']);
    $colleague = User::factory()->create([
        'department_id' => $departmentA->getKey(),
        'email' => 'colleague@salesflow.test',
    ]);
    $outsider = User::factory()->create([
        'department_id' => $departmentB->getKey(),
        'email' => 'outsider@salesflow.test',
    ]);

    Livewire::actingAs($manager)
        ->test(UserList::class)
        ->assertSee($manager->email)
        ->assertSee($colleague->email)
        ->assertSee('DEPT-A')
        ->assertDontSee($outsider->email)
        ->assertDontSee('DEPT-B');
});

it('denies the user list to roles without users view permission', function (): void {
    $viewer = listedUserWithRole('viewer');

    $this->actingAs($viewer)
        ->get('/settings/users')
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Người dùng');

    Livewire::actingAs($viewer)
        ->test(UserList::class)
        ->assertForbidden();
});
