<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ImportExport\ImportHistoryIndex;
use App\Models\Department;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\ImportExport\ImportHistoryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ImportHistoryTest extends TestCase
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

    public function test_service_queries_batches_and_summary_stats(): void
    {
        $service = app(ImportHistoryService::class);

        ImportBatch::create([
            'user_id' => $this->salesUser->id,
            'type' => 'lead',
            'temp_file_key' => 'tmp/leads.csv',
            'original_filename' => 'leads_danh_sach_2026.csv',
            'duplicate_strategy' => 'skip',
            'status' => 'completed',
            'total_rows' => 10,
            'processed_rows' => 10,
            'successful_rows' => 8,
            'failed_rows' => 2,
            'column_mapping' => ['full_name' => 'full_name'],
            'error_log' => [
                ['row' => 3, 'field' => 'email', 'message' => 'Email sai định dạng'],
                ['row' => 5, 'field' => 'phone', 'message' => 'Trùng số điện thoại'],
            ],
        ]);

        $stats = $service->getImportSummaryStats($this->salesUser);
        $this->assertEquals(1, $stats['total_batches']);
        $this->assertEquals(1, $stats['completed_batches']);
        $this->assertEquals(8, $stats['total_successful_rows']);
        $this->assertEquals(2, $stats['total_failed_rows']);

        $batches = $service->getPaginatedBatches($this->salesUser);
        $this->assertCount(1, $batches);
        $this->assertEquals('leads_danh_sach_2026.csv', $batches->first()?->original_filename);
    }

    public function test_livewire_import_history_index_renders_and_filters(): void
    {
        ImportBatch::create([
            'user_id' => $this->salesUser->id,
            'type' => 'company',
            'temp_file_key' => 'tmp/companies.csv',
            'original_filename' => 'cong_ty_khach_hang.csv',
            'duplicate_strategy' => 'skip',
            'status' => 'completed',
            'total_rows' => 5,
            'processed_rows' => 5,
            'successful_rows' => 5,
            'failed_rows' => 0,
            'column_mapping' => ['name' => 'name'],
        ]);

        $this->actingAs($this->salesUser);

        Livewire::test(ImportHistoryIndex::class)
            ->assertOk()
            ->assertSee('cong_ty_khach_hang.csv')
            ->set('type', 'lead')
            ->assertDontSee('cong_ty_khach_hang.csv');
    }

    public function test_error_log_csv_download_endpoint(): void
    {
        $batch = ImportBatch::create([
            'user_id' => $this->salesUser->id,
            'type' => 'lead',
            'temp_file_key' => 'tmp/test_err.csv',
            'original_filename' => 'danh_sach_loi.csv',
            'duplicate_strategy' => 'skip',
            'status' => 'completed',
            'total_rows' => 5,
            'processed_rows' => 5,
            'successful_rows' => 4,
            'failed_rows' => 1,
            'column_mapping' => ['full_name' => 'full_name'],
            'error_log' => [
                ['row' => 2, 'field' => 'phone', 'message' => 'Số điện thoại không hợp lệ'],
            ],
        ]);

        $response = $this->actingAs($this->salesUser)
            ->get(route('imports.history.download-errors', $batch->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
