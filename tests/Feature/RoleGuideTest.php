<?php

use App\Models\User;
use App\Services\RoleGuideService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function guideUserWithRole(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('protects the role guide behind authentication', function (): void {
    $this->get('/help/roles')->assertRedirect('/login');
});

it('shows the current role scope and permissions from the backend catalog', function (): void {
    $manager = guideUserWithRole('sales-manager');

    $this->actingAs($manager)
        ->get('/help/roles')
        ->assertOk()
        ->assertSee('Quyền hiện tại của bạn')
        ->assertSee('Sales Manager')
        ->assertSee('Dữ liệu trong phòng ban')
        ->assertSee('40 quyền')
        ->assertSee('Xem người dùng')
        ->assertSee('Gán người phụ trách lead')
        ->assertSee('Vai trò của bạn')
        ->assertSee('x-collapse.duration.200ms', false)
        ->assertSee('aria-controls="role-panel-sales-manager"', false)
        ->assertDontSee('<details', false);
});

it('allows viewers to open the guide and see its navigation item', function (): void {
    $viewer = guideUserWithRole('viewer');

    $this->actingAs($viewer)
        ->get('/help/roles')
        ->assertOk()
        ->assertSee('Viewer')
        ->assertSee('Chỉ đọc dữ liệu được cấp quyền')
        ->assertSee('8 quyền');

    $this->actingAs($viewer)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Vai trò & quyền', false);
});

it('documents super admin gate bypass and keeps role counts aligned with config', function (): void {
    $superAdmin = guideUserWithRole('super-admin');
    $catalog = app(RoleGuideService::class)->catalog();

    expect(array_column($catalog, 'permission_count'))->toBe([45, 45, 40, 30, 8]);

    $this->actingAs($superAdmin)
        ->get('/help/roles')
        ->assertOk()
        ->assertSee('Super Admin')
        ->assertSee('Toàn quyền hệ thống')
        ->assertSee('quyền được bổ sung trong tương lai');
});
