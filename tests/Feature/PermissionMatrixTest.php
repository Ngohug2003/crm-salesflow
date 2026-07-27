<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Roles\PermissionMatrixView;
use App\Models\Department;
use App\Models\User;
use App\Services\Rbac\PermissionMatrixService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);

        $this->superAdmin = User::factory()->create(['department_id' => $dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['department_id' => $dept->id]);
        $this->admin->assignRole('admin');

        $this->salesUser = User::factory()->create(['department_id' => $dept->id]);
        $this->salesUser->assignRole('sales');
    }

    public function test_matrix_service_returns_roles_and_modules(): void
    {
        $service = app(PermissionMatrixService::class);
        $matrix = $service->getMatrix();

        $this->assertArrayHasKey('roles', $matrix);
        $this->assertArrayHasKey('modules', $matrix);
        $this->assertCount(5, $matrix['roles']);
        $this->assertCount(7, $matrix['modules']);

        $roleNames = array_column($matrix['roles'], 'name');
        $this->assertContains('super-admin', $roleNames);
        $this->assertContains('admin', $roleNames);
        $this->assertContains('sales-manager', $roleNames);
        $this->assertContains('sales', $roleNames);
        $this->assertContains('viewer', $roleNames);
    }

    public function test_route_authorization(): void
    {
        // Guests redirect to login
        $this->get(route('roles.permission-matrix'))
            ->assertRedirect(route('login'));

        // Sales user gets 403 Forbidden
        $this->actingAs($this->salesUser)
            ->get(route('roles.permission-matrix'))
            ->assertForbidden();

        // Admin gets 200 OK
        $this->actingAs($this->admin)
            ->get(route('roles.permission-matrix'))
            ->assertOk();

        // Super Admin gets 200 OK
        $this->actingAs($this->superAdmin)
            ->get(route('roles.permission-matrix'))
            ->assertOk();
    }

    public function test_livewire_permission_matrix_view_renders_matrix_and_filters(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PermissionMatrixView::class)
            ->assertOk()
            ->assertSee('Ma trận phân quyền (Permission Matrix)')
            ->assertSee('Super Admin')
            ->assertSee('Sales Manager')
            ->assertSee('leads.view')
            ->assertSee('opportunities.create')
            ->set('module', 'customers')
            ->assertSee('1. Khách hàng')
            ->set('search', 'leads.export')
            ->assertSee('leads.export')
            ->call('clearFilters')
            ->assertSet('module', 'all')
            ->assertSet('search', '');
    }
}
