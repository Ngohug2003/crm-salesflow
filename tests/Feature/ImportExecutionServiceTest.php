<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessImportChunkJob;
use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\User;
use App\Services\Import\ImportExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ImportExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_it_creates_batch_and_dispatches_chunk_jobs(): void
    {
        Queue::fake();

        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        // Create temp CSV file
        $csvContent = "Họ,Tên,Email,Số điện thoại,Công ty\nNguyễn,Văn A,nva@example.com,0912345678,Công ty A\n";
        $fileKey = 'temp_test_123.csv';
        Storage::disk('local')->put("imports/temp/{$fileKey}", $csvContent);

        $service = new ImportExecutionService;
        $batch = $service->execute(
            $admin,
            $fileKey,
            'test_leads.csv',
            ['last_name' => 'Họ', 'first_name' => 'Tên', 'email' => 'Email', 'phone' => 'Số điện thoại', 'company_name' => 'Công ty'],
            'skip',
        );

        $this->assertInstanceOf(ImportBatch::class, $batch);
        $this->assertSame($admin->id, $batch->user_id);
        $this->assertSame(1, $batch->total_rows);
        $this->assertSame('skip', $batch->duplicate_strategy);

        Queue::assertPushed(ProcessImportChunkJob::class, 1);
    }

    public function test_job_skips_existing_lead_on_skip_strategy(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        // Seed existing lead
        Lead::factory()->create([
            'email' => 'duplicate@example.com',
            'full_name' => 'Original Lead',
            'owner_id' => $admin->id,
        ]);

        $batch = ImportBatch::query()->create([
            'user_id' => $admin->id,
            'type' => 'leads',
            'temp_file_key' => 'key.csv',
            'original_filename' => 'file.csv',
            'duplicate_strategy' => 'skip',
            'status' => 'processing',
            'total_rows' => 1,
            'column_mapping' => ['last_name' => 'Họ', 'first_name' => 'Tên', 'email' => 'Email'],
        ]);

        $job = new ProcessImportChunkJob(
            $batch->id,
            $admin->id,
            [['Họ' => 'Nguyễn', 'Tên' => 'Mới', 'Email' => 'duplicate@example.com']],
            ['last_name' => 'Họ', 'first_name' => 'Tên', 'email' => 'Email'],
            'skip',
        );

        $job->handle();

        $freshBatch = $batch->fresh();
        $this->assertSame(1, $freshBatch->skipped_rows);
        $this->assertSame(0, $freshBatch->successful_rows);

        $lead = Lead::query()->where('email', 'duplicate@example.com')->sole();
        $this->assertSame('Original Lead', $lead->full_name);
    }

    public function test_job_updates_existing_lead_on_update_strategy(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        // Seed existing lead
        $lead = Lead::factory()->create([
            'email' => 'update@example.com',
            'full_name' => 'Old FullName',
            'company_name' => 'Old Company',
            'owner_id' => $admin->id,
        ]);

        $batch = ImportBatch::query()->create([
            'user_id' => $admin->id,
            'type' => 'leads',
            'temp_file_key' => 'key.csv',
            'original_filename' => 'file.csv',
            'duplicate_strategy' => 'update',
            'status' => 'processing',
            'total_rows' => 1,
            'column_mapping' => ['last_name' => 'Họ', 'first_name' => 'Tên', 'email' => 'Email', 'company_name' => 'Công ty'],
        ]);

        $job = new ProcessImportChunkJob(
            $batch->id,
            $admin->id,
            [['Họ' => 'NewLastName', 'Tên' => 'NewName', 'Email' => 'update@example.com', 'Công ty' => 'New Enterprise']],
            ['last_name' => 'Họ', 'first_name' => 'Tên', 'email' => 'Email', 'company_name' => 'Công ty'],
            'update',
        );

        $job->handle();

        $freshBatch = $batch->fresh();
        $this->assertSame(1, $freshBatch->successful_rows);

        $freshLead = $lead->fresh();
        $this->assertSame('NewLastName NewName', $freshLead->full_name);
        $this->assertSame('New Enterprise', $freshLead->company_name);
    }
}
