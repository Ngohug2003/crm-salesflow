<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessQueuedExportJob;
use App\Models\ExportBatch;
use App\Models\Lead;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use App\Services\Export\ExportExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ExportExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_it_creates_export_batch_and_dispatches_queued_job(): void
    {
        Queue::fake();

        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $service = new ExportExecutionService;
        $batch = $service->requestExport($admin, 'leads', ['search' => 'Test']);

        $this->assertInstanceOf(ExportBatch::class, $batch);
        $this->assertSame($admin->id, $batch->user_id);
        $this->assertSame('pending', $batch->status);

        Queue::assertPushed(ProcessQueuedExportJob::class, 1);
    }

    public function test_job_processes_export_file_with_user_data_scope(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        Lead::factory()->count(3)->create([
            'owner_id' => $admin->id,
            'department_id' => $admin->department_id,
        ]);

        $batch = ExportBatch::query()->create([
            'user_id' => $admin->id,
            'type' => 'leads',
            'status' => 'pending',
        ]);

        $job = new ProcessQueuedExportJob($batch->id);
        $job->handle(app(DataScopeService::class));

        $freshBatch = $batch->fresh();
        $this->assertSame('completed', $freshBatch->status, $freshBatch->error_message ?? '');
        $this->assertGreaterThan(0, $freshBatch->total_rows);
        $this->assertNotNull($freshBatch->file_path);

        Storage::disk('local')->assertExists($freshBatch->file_path);
    }

    public function test_signed_download_url_allows_authorized_user_to_download_csv(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $filePath = "exports/{$admin->id}/leads_export_#1.csv";
        Storage::disk('local')->put($filePath, "\xEF\xBB\xBFMã Lead,Họ và Tên\n1,Nguyễn Văn A\n");

        $batch = ExportBatch::query()->create([
            'user_id' => $admin->id,
            'type' => 'leads',
            'status' => 'completed',
            'file_path' => $filePath,
            'file_name' => 'leads_export_#1.csv',
            'total_rows' => 1,
        ]);

        $service = new ExportExecutionService;
        $signedUrl = $service->getSignedDownloadUrl($batch);

        $this->actingAs($admin)
            ->get($signedUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_signed_download_rejects_tampered_signature(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $batch = ExportBatch::query()->create([
            'user_id' => $admin->id,
            'type' => 'leads',
            'status' => 'completed',
            'file_path' => 'exports/invalid.csv',
        ]);

        $service = new ExportExecutionService;
        $signedUrl = $service->getSignedDownloadUrl($batch);
        $tamperedUrl = $signedUrl.'tampered';

        $this->actingAs($admin)
            ->get($tamperedUrl)
            ->assertForbidden();
    }
}
