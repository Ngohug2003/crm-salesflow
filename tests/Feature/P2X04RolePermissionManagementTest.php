<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Roles\PermissionMatrixView;
use App\Models\Company;
use App\Models\RolePermissionCustomization;
use App\Models\User;
use App\Services\Rbac\RolePermissionManagementService;
use App\Services\RoleGuideService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class P2X04RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = $this->userWithRole('super-admin');
        $this->admin = $this->userWithRole('admin');
        $this->sales = $this->userWithRole('sales');
    }

    public function test_admin_can_update_sales_permissions_from_the_matrix(): void
    {
        Context::add('request_id', 'req-p2x04-permission-update');

        Livewire::actingAs($this->admin)
            ->test(PermissionMatrixView::class)
            ->call('selectRole', 'sales')
            ->assertSet('selectedRole', 'sales')
            ->call('togglePermission', 'companies.update')
            ->assertSet('showSaveConfirmation', false)
            ->call('openSaveConfirmation')
            ->assertSet('showSaveConfirmation', true)
            ->call('savePermissions')
            ->assertSet('showSaveConfirmation', false)
            ->assertSee('Đã cập nhật quyền cho vai trò thành công.');

        self::assertFalse(Role::findByName('sales')->hasPermissionTo('companies.update'));
        $salesGuide = collect(app(RoleGuideService::class)->catalog())->firstWhere('name', 'sales');
        self::assertNotNull($salesGuide);
        self::assertSame(
            count(Role::findByName('sales')->permissions),
            $salesGuide['permission_count'],
        );
        self::assertDatabaseHas('role_permission_customizations', [
            'role_id' => Role::findByName('sales')->getKey(),
            'customized_by' => $this->admin->getKey(),
        ]);

        $audit = Activity::query()->where('event', 'role.permissions.updated')->latest('id')->first();
        self::assertNotNull($audit);
        self::assertSame('req-p2x04-permission-update', $audit->request_id);
        self::assertSame('sales', $audit->properties->get('role_name'));
        self::assertContains('companies.update', $audit->properties->get('removed_permissions'));
    }

    public function test_permission_change_immediately_hides_navigation_and_denies_direct_routes(): void
    {
        $service = app(RolePermissionManagementService::class);
        $permissions = $service->permissionsForRole('sales');
        $service->updateRolePermissions(
            $this->admin,
            'sales',
            array_values(array_diff($permissions, ['companies.update'])),
        );

        $company = Company::factory()->create([
            'owner_id' => $this->sales->getKey(),
        ]);

        $this->actingAs($this->sales)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('customers.merge'), false);

        $this->actingAs($this->sales)
            ->get(route('customers.merge'))
            ->assertForbidden();

        $this->actingAs($this->sales)
            ->get(route('companies.edit', $company->getKey()))
            ->assertForbidden();
    }

    public function test_admin_cannot_edit_equal_or_higher_roles_or_protected_permissions(): void
    {
        $service = app(RolePermissionManagementService::class);

        self::assertTrue($service->canEditRole($this->superAdmin, 'admin'));
        self::assertFalse($service->canEditRole($this->superAdmin, 'super-admin'));
        self::assertFalse($service->canEditRole($this->admin, 'admin'));
        self::assertFalse($service->canEditRole($this->admin, 'super-admin'));
        self::assertFalse($service->canTogglePermission($this->admin, 'sales', 'settings.manage'));

        $this->expectException(AuthorizationException::class);
        $service->updateRolePermissions(
            $this->admin,
            'admin',
            $service->permissionsForRole('admin'),
        );
    }

    public function test_backend_rejects_tampering_with_a_protected_permission(): void
    {
        $service = app(RolePermissionManagementService::class);
        $permissions = $service->permissionsForRole('sales');
        $permissions[] = 'settings.manage';

        $this->expectException(AuthorizationException::class);
        $service->updateRolePermissions($this->admin, 'sales', $permissions);
    }

    public function test_seeder_preserves_ui_customizations_and_reset_restores_defaults(): void
    {
        $service = app(RolePermissionManagementService::class);
        $permissions = array_values(array_diff(
            $service->permissionsForRole('sales'),
            ['companies.update'],
        ));

        $service->updateRolePermissions($this->admin, 'sales', $permissions);
        $this->seed(RolePermissionSeeder::class);

        self::assertFalse(Role::findByName('sales')->hasPermissionTo('companies.update'));

        $service->resetRolePermissions($this->admin, 'sales');

        self::assertTrue(Role::findByName('sales')->hasPermissionTo('companies.update'));
        self::assertFalse(RolePermissionCustomization::query()
            ->where('role_id', Role::findByName('sales')->getKey())
            ->exists());
    }

    public function test_sales_cannot_open_or_call_the_permission_editor(): void
    {
        $this->actingAs($this->sales)
            ->get(route('roles.permission-matrix'))
            ->assertForbidden();

        Livewire::actingAs($this->sales)
            ->test(PermissionMatrixView::class)
            ->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
