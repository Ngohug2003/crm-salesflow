<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\ReportFilterData;
use App\Livewire\Dashboard\DashboardOverview;
use App\Livewire\Reports\FunnelReport;
use App\Livewire\Reports\RevenueReport;
use App\Livewire\Reports\SalesPerformanceReport;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Analytics\SalesMetricsQueryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

final class Phase7CheckpointTest extends TestCase
{
    use RefreshDatabase;

    private Department $deptA;

    private User $admin;

    private User $salesA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->deptA = Department::factory()->create(['name' => 'Phòng Kinh doanh Checkpoint']);

        $this->admin = User::factory()->create(['department_id' => $this->deptA->id]);
        $this->admin->assignRole('admin');

        $this->salesA = User::factory()->create(['department_id' => $this->deptA->id]);
        $this->salesA->assignRole('sales');
    }

    public function test_phase7_all_pages_render_successfully_for_admin(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('dashboard'))->assertOk()->assertSeeLivewire(DashboardOverview::class);
        $this->get(route('reports.funnel'))->assertOk()->assertSeeLivewire(FunnelReport::class);
        $this->get(route('reports.revenue'))->assertOk()->assertSeeLivewire(RevenueReport::class);
        $this->get(route('reports.performance'))->assertOk()->assertSeeLivewire(SalesPerformanceReport::class);
    }

    public function test_metrics_caching_layer_hits_cache_and_flushes_on_request(): void
    {
        $this->actingAs($this->admin);

        $pipeline = Pipeline::factory()->create();
        $stageWon = PipelineStage::factory()->create(['pipeline_id' => $pipeline->id, 'is_won' => true]);

        Opportunity::factory()->create([
            'owner_id' => $this->salesA->id,
            'department_id' => $this->deptA->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageWon->id,
            'amount' => 100000000,
            'is_won' => true,
            'created_at' => now(),
        ]);

        /** @var SalesMetricsQueryService $service */
        $service = app(SalesMetricsQueryService::class);
        $filters = new ReportFilterData(datePreset: 'this_month');

        // Initial fetch populates cache
        $metrics1 = $service->getOpportunityMetrics($this->admin, $filters);
        $this->assertEquals(100000000, $metrics1['won_amount']);

        // Create new opportunity directly in DB
        Opportunity::factory()->create([
            'owner_id' => $this->salesA->id,
            'department_id' => $this->deptA->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageWon->id,
            'amount' => 50000000,
            'is_won' => true,
            'created_at' => now(),
        ]);

        // Second fetch reads from cache (cached won_amount remains 100M)
        $metricsCached = $service->getOpportunityMetrics($this->admin, $filters);
        $this->assertEquals(100000000, $metricsCached['won_amount']);

        // Flush cache via clearMetricsCache
        $service->clearMetricsCache($this->admin);

        // Fetch after flush recalculates fresh data (150M)
        $metricsFresh = $service->getOpportunityMetrics($this->admin, $filters);
        $this->assertEquals(150000000, $metricsFresh['won_amount']);
    }

    public function test_clear_cache_and_reload_action_in_livewire_components(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(DashboardOverview::class)
            ->call('clearCacheAndReload')
            ->assertSet('datePreset', 'this_month');

        Livewire::test(FunnelReport::class)
            ->call('clearCacheAndReload')
            ->assertSet('datePreset', 'this_month');

        Livewire::test(RevenueReport::class)
            ->call('clearCacheAndReload')
            ->assertSet('datePreset', 'this_month');

        Livewire::test(SalesPerformanceReport::class)
            ->call('clearCacheAndReload')
            ->assertSet('datePreset', 'this_month');
    }
}
