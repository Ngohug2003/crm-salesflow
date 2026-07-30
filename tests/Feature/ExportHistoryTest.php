<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ImportExport\ExportHistoryIndex;
use App\Models\Department;
use App\Models\ExportBatch;
use App\Models\User;
use App\Services\ImportExport\ExportHistoryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ExportHistoryTest extends TestCase
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

    public function test_service_queries_export_batches_and_stats(): void
    {
        $service = app(ExportHistoryService::class);

        ExportBatch::create([
            'user_id' => $this->salesUser->id,
            'type' => 'lead',
            'file_name' => 'danh_sach_leads_2026.csv',
            'file_size' => 1572864, // ~1.5 MB
            'status' => 'completed',
            'total_rows' => 150,
            'completed_at' => now(),
        ]);

        $stats = $service->getExportSummaryStats($this->salesUser);
        $this->assertEquals(1, $stats['total_batches']);
        $this->assertEquals(1, $stats['completed_batches']);
        $this->assertEquals(150, $stats['total_exported_rows']);
        $this->assertEquals('1.5 MB', $stats['total_file_size_formatted']);

        $batches = $service->getPaginatedBatches($this->salesUser);
        $this->assertCount(1, $batches);
        $this->assertEquals('danh_sach_leads_2026.csv', $batches->first()?->file_name);
    }

    public function test_livewire_export_history_index_renders_and_filters(): void
    {
        ExportBatch::create([
            'user_id' => $this->salesUser->id,
            'type' => 'company',
            'file_name' => 'danh_sach_doanh_nghiep.csv',
            'file_size' => 512000,
            'status' => 'completed',
            'total_rows' => 45,
            'completed_at' => now(),
        ]);

        $this->actingAs($this->salesUser);

        Livewire::test(ExportHistoryIndex::class)
            ->assertOk()
            ->assertSee('danh_sach_doanh_nghiep.csv')
            ->set('type', 'lead')
            ->assertDontSee('danh_sach_doanh_nghiep.csv');
    }

    public function test_export_file_download_endpoint(): void
    {
        $batch = ExportBatch::create([
            'user_id' => $this->salesUser->id,
            'type' => 'opportunity',
            'file_name' => 'co_hoi_ban_hang.csv',
            'file_size' => 2048,
            'status' => 'completed',
            'total_rows' => 10,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->salesUser)
            ->get(route('exports.history.download', $batch->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
