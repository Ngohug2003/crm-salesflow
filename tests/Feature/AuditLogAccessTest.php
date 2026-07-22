<?php

use App\Livewire\AuditLogs\AuditLogList;
use App\Models\Department;
use App\Models\User;
use App\Services\SystemAuditService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function auditUser(string $role, ?Department $department = null): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('only allows super admins and IT admins to view audit logs', function (): void {
    $it = Department::factory()->create(['code' => 'IT']);
    $sales = Department::factory()->create(['code' => 'SALES']);
    $superAdmin = auditUser('super-admin', $sales);
    $itAdmin = auditUser('admin', $it);
    $regularAdmin = auditUser('admin', $sales);
    $itManager = auditUser('sales-manager', $it);

    $this->actingAs($superAdmin)->get(route('audit-logs.index'))->assertOk()->assertSee('Nhật ký kiểm toán');
    $this->actingAs($itAdmin)->get(route('audit-logs.index'))->assertOk()->assertSee('Nhật ký kiểm toán');
    $this->actingAs($regularAdmin)->get(route('audit-logs.index'))->assertForbidden();
    $this->actingAs($itManager)->get(route('audit-logs.index'))->assertForbidden();
});

it('shows both table and console log views with persistent URL state', function (): void {
    $it = Department::factory()->create(['code' => 'IT']);
    $itAdmin = auditUser('admin', $it);
    $target = auditUser('sales');

    app(SystemAuditService::class)->record(
        $itAdmin,
        $target,
        'updated',
        'Cập nhật người dùng thử nghiệm',
        ['name' => 'Tên cũ'],
        ['name' => 'Tên mới'],
    );
    Activity::query()->sole()->forceFill([
        'created_at' => '2026-07-22 12:39:25',
        'updated_at' => '2026-07-22 12:39:25',
    ])->save();

    Livewire::actingAs($itAdmin)
        ->withQueryParams(['view' => 'log'])
        ->test(AuditLogList::class)
        ->assertSet('viewMode', 'log')
        ->assertSee('Nhật ký hoạt động hệ thống')
        ->assertSee('Cập nhật người dùng thử nghiệm')
        ->assertSee('22/07/2026 12:39:25')
        ->set('viewMode', 'table')
        ->assertSee('Người thực hiện')
        ->assertSee('Dữ liệu trước');
});

it('filters Vietnamese calendar dates against local database timestamps', function (): void {
    $it = Department::factory()->create(['code' => 'IT']);
    $itAdmin = auditUser('admin', $it);
    $target = auditUser('sales');
    $audit = app(SystemAuditService::class);

    $audit->record($itAdmin, $target, 'updated', 'Đầu ngày Việt Nam', null, ['name' => 'Included']);
    Activity::query()->latest('id')->firstOrFail()->forceFill([
        'created_at' => '2026-07-22 01:30:00',
        'updated_at' => '2026-07-22 01:30:00',
    ])->save();

    $audit->record($itAdmin, $target, 'updated', 'Sang ngày kế tiếp', null, ['name' => 'Excluded']);
    Activity::query()->latest('id')->firstOrFail()->forceFill([
        'created_at' => '2026-07-23 00:30:00',
        'updated_at' => '2026-07-23 00:30:00',
    ])->save();

    Livewire::actingAs($itAdmin)
        ->test(AuditLogList::class)
        ->set('dateFrom', '2026-07-22')
        ->set('dateTo', '2026-07-22')
        ->assertSee('Đầu ngày Việt Nam')
        ->assertDontSee('Sang ngày kế tiếp');
});

it('hides sensitive values from nested audit properties', function (): void {
    $actor = auditUser('super-admin');
    $target = auditUser('sales');

    app(SystemAuditService::class)->record(
        $actor,
        $target,
        'updated',
        'Kiểm tra che dữ liệu',
        ['profile' => ['password' => 'old-secret', 'name' => 'Old']],
        ['profile' => ['password_hash' => 'new-secret', 'name' => 'New']],
        ['token' => 'private-token'],
    );

    $properties = Activity::query()->sole()->properties->toJson();

    expect($properties)->not->toContain('old-secret')
        ->not->toContain('new-secret')
        ->not->toContain('private-token')
        ->toContain('Old')
        ->toContain('New');
});
