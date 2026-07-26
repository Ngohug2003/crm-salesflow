<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\ReportFilterData;
use App\Enums\LeadStatus;
use App\Livewire\Dashboard\DashboardOverview;
use App\Models\Department;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use App\Services\Analytics\ReportFilterOptionsService;
use App\Services\Analytics\SalesMetricsQueryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

final class Phase7ReportConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_invalid_or_reversed_custom_dates_are_normalized_without_exception(): void
    {
        [$invalidStart, $invalidEnd] = (new ReportFilterData(
            datePreset: 'custom',
            startDate: 'not-a-date',
            endDate: 'also-invalid',
        ))->resolveDateRange();

        [$reversedStart, $reversedEnd] = (new ReportFilterData(
            datePreset: 'custom',
            startDate: '2026-07-20',
            endDate: '2026-07-01',
        ))->resolveDateRange();

        self::assertTrue($invalidStart->isStartOfMonth());
        self::assertTrue($invalidEnd->isEndOfMonth());
        self::assertSame('2026-07-01', $reversedStart->toDateString());
        self::assertSame('2026-07-20', $reversedEnd->toDateString());
    }

    public function test_revenue_uses_actual_close_date_and_forecast_uses_expected_close_date(): void
    {
        [$pipeline, $openStage, $wonStage] = $this->pipelineWithStages();
        Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $wonStage->id,
            'owner_id' => $this->admin->id,
            'amount' => 1000000,
            'is_won' => true,
            'is_lost' => false,
            'created_at' => now()->subMonths(2),
            'actual_close_date' => now()->toDateString(),
        ]);
        Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $openStage->id,
            'owner_id' => $this->admin->id,
            'amount' => 2000000,
            'is_won' => false,
            'is_lost' => false,
            'created_at' => now()->subMonths(2),
            'expected_close_date' => now()->toDateString(),
        ]);

        $metrics = app(SalesMetricsQueryService::class)->getOpportunityMetrics(
            $this->admin,
            new ReportFilterData(datePreset: 'this_month', pipelineId: $pipeline->id),
        );

        self::assertSame(1, $metrics['won_opportunities']);
        self::assertSame(1000000.0, $metrics['won_amount']);
        self::assertSame(1, $metrics['open_opportunities']);
        self::assertSame(1000000.0, $metrics['weighted_forecast']);
    }

    public function test_funnel_counts_each_stage_reached_from_transition_history(): void
    {
        [$pipeline, $firstStage, $secondStage] = $this->pipelineWithStages();
        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $secondStage->id,
            'owner_id' => $this->admin->id,
            'created_at' => now(),
        ]);
        OpportunityStageHistory::query()->create([
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => $firstStage->id,
            'to_stage_id' => $secondStage->id,
            'user_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        $funnel = app(SalesMetricsQueryService::class)->getFunnelMetrics(
            $this->admin,
            new ReportFilterData(pipelineId: $pipeline->id),
        );

        self::assertSame([1, 1], array_column($funnel, 'opportunity_count'));
        self::assertSame(100.0, $funnel[1]['conversion_from_previous']);
    }

    public function test_filter_options_follow_owned_data_scope(): void
    {
        $department = Department::factory()->create();
        $sales = User::factory()->create(['department_id' => $department->id]);
        $sales->assignRole('sales');
        User::factory()->create();

        $service = app(ReportFilterOptionsService::class);

        self::assertSame([$department->id], $service->departments($sales)->pluck('id')->all());
        self::assertSame([$sales->id], $service->users($sales)->pluck('id')->all());
    }

    public function test_refreshing_report_cache_does_not_remove_unrelated_cache_entries(): void
    {
        Cache::put('unrelated-feature-value', 'kept', 60);

        app(SalesMetricsQueryService::class)->clearMetricsCache($this->admin);

        self::assertSame('kept', Cache::get('unrelated-feature-value'));
    }

    public function test_completed_task_is_counted_for_each_pivot_assignee(): void
    {
        $assignee = User::factory()->create();
        $task = Task::query()->create([
            'title' => 'Công việc kiểm thử báo cáo',
            'created_by' => $this->admin->id,
            'status' => 'completed',
            'priority' => 'medium',
            'completed_at' => now(),
        ]);
        $task->assignees()->attach($assignee->id);

        $performance = app(SalesMetricsQueryService::class)->getSalesPerformanceMetrics(
            $this->admin,
            new ReportFilterData(userId: $assignee->id),
        );

        self::assertSame(1, $performance[0]['completed_tasks_count']);
    }

    public function test_standardized_dashboard_and_report_views_render(): void
    {
        $this->pipelineWithStages();

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tổng quan bán hàng');

        $this->get(route('reports.funnel'))
            ->assertOk()
            ->assertSee('Phễu chuyển đổi');

        $this->get(route('reports.revenue'))
            ->assertOk()
            ->assertSee('Doanh thu và dự báo');

        $this->get(route('reports.performance'))
            ->assertOk()
            ->assertSee('Hiệu suất bán hàng');
    }

    public function test_navigation_resets_filters_per_page_and_lead_status_uses_vietnamese_label(): void
    {
        $department = Department::factory()->create();
        [$pipeline] = $this->pipelineWithStages();
        Lead::factory()->create([
            'owner_id' => $this->admin->id,
            'department_id' => $department->id,
            'status' => LeadStatus::Contacted,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(DashboardOverview::class)
            ->set('datePreset', 'custom')
            ->set('startDate', now()->startOfMonth()->toDateString())
            ->set('endDate', now()->endOfMonth()->toDateString())
            ->set('departmentId', $department->id)
            ->set('userId', $this->admin->id)
            ->set('pipelineId', $pipeline->id);

        $component
            ->assertSee('href="'.route('reports.funnel').'"', escape: false)
            ->assertDontSee('href="'.route('reports.funnel', [
                'preset' => 'custom',
                'dept' => $department->id,
                'user' => $this->admin->id,
                'pipeline' => $pipeline->id,
            ]).'"', escape: false);

        $metrics = app(SalesMetricsQueryService::class)->getLeadMetrics(
            $this->admin,
            new ReportFilterData(departmentId: $department->id),
        );

        self::assertSame(1, $metrics['by_status']['Đã liên hệ']);
    }

    /** @return array{Pipeline, PipelineStage, PipelineStage} */
    private function pipelineWithStages(): array
    {
        $pipeline = Pipeline::factory()->create(['owner_id' => $this->admin->id]);
        $first = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
            'probability' => 50,
        ]);
        $second = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
            'probability' => 100,
            'is_won' => true,
        ]);

        return [$pipeline, $first, $second];
    }
}
