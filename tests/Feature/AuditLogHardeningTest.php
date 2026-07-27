<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Export\ExportExecutionService;
use App\Services\Import\ImportExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

final class AuditLogHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_it_records_audit_log_when_import_is_initiated(): void
    {
        Queue::fake();

        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $csvContent = "Họ,Tên,Email\nNguyễn,Văn A,nva@example.com\n";
        $fileKey = 'temp_audit_1.csv';
        Storage::disk('local')->put("imports/temp/{$fileKey}", $csvContent);

        $service = new ImportExecutionService;
        $batch = $service->execute(
            $admin,
            $fileKey,
            'import_leads.csv',
            ['last_name' => 'Họ', 'first_name' => 'Tên', 'email' => 'Email'],
            'skip',
        );

        $activity = Activity::query()
            ->where('log_name', 'import')
            ->where('subject_type', get_class($batch))
            ->where('subject_id', $batch->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('Khởi tạo đợt import lead hàng loạt', $activity->description);
        $this->assertSame($admin->id, $activity->causer_id);
    }

    public function test_it_records_audit_log_when_export_is_initiated(): void
    {
        Queue::fake();

        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $service = new ExportExecutionService;
        $batch = $service->requestExport($admin, 'leads', ['search' => 'AuditTest']);

        $activity = Activity::query()
            ->where('log_name', 'export')
            ->where('subject_type', get_class($batch))
            ->where('subject_id', $batch->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('Yêu cầu xuất dữ liệu CSV', $activity->description);
        $this->assertSame($admin->id, $activity->causer_id);
    }

    public function test_audit_log_route_is_protected_for_it_or_super_admin_only(): void
    {
        $salesUser = User::query()->where('email', 'demo04@salesflow.test')->sole();
        $salesUser->update(['email_verified_at' => now(), 'is_active' => true]);

        $this->actingAs($salesUser)
            ->get(route('audit-logs.index'))
            ->assertForbidden();

        $superAdmin = User::query()->where('email', 'admin@salesflow.test')->sole();
        $superAdmin->update(['email_verified_at' => now(), 'is_active' => true]);

        $this->actingAs($superAdmin)
            ->get(route('audit-logs.index'))
            ->assertOk();
    }
}
