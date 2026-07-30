<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AuditLogs\AuditLogList;
use App\Models\Department;
use App\Models\Lead;
use App\Models\User;
use App\Services\Audit\AuditLogDetailService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

final class AuditLogDetailDrawerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);

        $this->superAdmin = User::factory()->create(['department_id' => $dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $this->salesUser = User::factory()->create(['department_id' => $dept->id]);
        $this->salesUser->assignRole('sales');
    }

    public function test_audit_detail_service_parses_field_changes_diff(): void
    {
        $service = app(AuditLogDetailService::class);

        $lead = Lead::factory()->create([
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0901234567',
        ]);

        $activity = activity()
            ->causedBy($this->superAdmin)
            ->performedOn($lead)
            ->event('updated')
            ->withProperties([
                'old' => ['full_name' => 'Nguyễn Văn A', 'phone' => '0901234567'],
                'attributes' => ['full_name' => 'Nguyễn Văn B', 'phone' => '0901234567'],
                'ip_address' => '192.168.1.1',
                'request_id' => 'req_test_123',
            ])
            ->log('Cập nhật thông tin khách hàng');

        /** @var Activity $activityRecord */
        $activityRecord = Activity::latest()->first();

        $detail = $service->getAuditDetail((int) $activityRecord->id);
        $this->assertNotNull($detail);
        $this->assertEquals('req_test_123', $detail['request_id']);
        $this->assertEquals('192.168.1.1', $detail['ip_address']);
        $this->assertCount(1, $detail['changes']);
        $this->assertEquals('full_name', $detail['changes'][0]['field']);
        $this->assertEquals('Nguyễn Văn A', $detail['changes'][0]['old']);
        $this->assertEquals('Nguyễn Văn B', $detail['changes'][0]['new']);
    }

    public function test_audit_detail_service_parses_old_vs_new_format(): void
    {
        $service = app(AuditLogDetailService::class);

        $activity = activity()
            ->causedBy($this->superAdmin)
            ->event('updated')
            ->withProperties([
                'old' => ['status' => 'new'],
                'new' => ['status' => 'contacted'],
            ])
            ->log('Thay đổi trạng thái');

        /** @var Activity $activityRecord */
        $activityRecord = Activity::latest()->first();

        $detail = $service->getAuditDetail((int) $activityRecord->id);
        $this->assertNotNull($detail);
        $this->assertCount(1, $detail['changes']);
        $this->assertEquals('status', $detail['changes'][0]['field']);
        $this->assertEquals('new', $detail['changes'][0]['old']);
        $this->assertEquals('contacted', $detail['changes'][0]['new']);
    }

    public function test_livewire_audit_list_opens_drawer_and_renders_detail(): void
    {
        $activity = activity()
            ->causedBy($this->superAdmin)
            ->event('created')
            ->withProperties([
                'attributes' => ['name' => 'Cơ hội mới'],
                'request_id' => 'req_drawer_999',
            ])
            ->log('Tạo mới cơ hội');

        /** @var Activity $activityRecord */
        $activityRecord = Activity::latest()->first();

        $this->actingAs($this->superAdmin);

        Livewire::test(AuditLogList::class)
            ->assertOk()
            ->call('selectActivity', (int) $activityRecord->id)
            ->assertSet('selectedActivityId', (int) $activityRecord->id)
            ->assertSee('Chi tiết Nhật ký kiểm toán #'.$activityRecord->id)
            ->assertSee('Tạo mới cơ hội')
            ->call('closeDrawer')
            ->assertSet('selectedActivityId', null);
    }
}
