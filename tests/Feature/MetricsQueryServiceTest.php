<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\ReportFilterData;
use App\Enums\ActivityType;
use App\Enums\LeadStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Department;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use App\Services\Analytics\SalesMetricsQueryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MetricsQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private SalesMetricsQueryService $service;

    private Department $deptA;

    private Department $deptB;

    private User $admin;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->service = app(SalesMetricsQueryService::class);

        // Tạo phòng ban
        $this->deptA = Department::factory()->create(['name' => 'Phòng Kinh doanh A']);
        $this->deptB = Department::factory()->create(['name' => 'Phòng Kinh doanh B']);

        $this->admin = User::factory()->create([
            'department_id' => $this->deptA->id,
        ]);
        $this->admin->assignRole('admin');

        $this->userA = User::factory()->create([
            'department_id' => $this->deptA->id,
        ]);
        $this->userA->assignRole('sales-manager');

        $this->userB = User::factory()->create([
            'department_id' => $this->deptB->id,
        ]);
        $this->userB->assignRole('sales');
    }

    public function test_it_calculates_lead_metrics_correctly(): void
    {
        // Tạo 3 leads cho userA trong tháng hiện tại
        Lead::factory()->create([
            'owner_id' => $this->userA->id,
            'department_id' => $this->deptA->id,
            'status' => LeadStatus::New->value,
            'created_at' => now(),
        ]);
        Lead::factory()->create([
            'owner_id' => $this->userA->id,
            'department_id' => $this->deptA->id,
            'status' => LeadStatus::Converted->value,
            'converted_at' => now(),
            'created_at' => now(),
        ]);

        // Lead cho userB
        Lead::factory()->create([
            'owner_id' => $this->userB->id,
            'department_id' => $this->deptB->id,
            'status' => LeadStatus::New->value,
            'created_at' => now(),
        ]);

        $filters = new ReportFilterData(datePreset: 'this_month');

        // User A (Department Scope) -> thấy 2 leads thuộc Dept A
        $metricsA = $this->service->getLeadMetrics($this->userA, $filters);
        $this->assertEquals(2, $metricsA['total_leads']);
        $this->assertEquals(1, $metricsA['converted_leads']);
        $this->assertEquals(50.0, $metricsA['conversion_rate']);

        // User B (Owned Scope) -> thấy 1 lead thuộc chính User B
        $metricsB = $this->service->getLeadMetrics($this->userB, $filters);
        $this->assertEquals(1, $metricsB['total_leads']);

        // Admin (All Scope) -> thấy cả 3 leads
        $metricsAdmin = $this->service->getLeadMetrics($this->admin, $filters);
        $this->assertEquals(3, $metricsAdmin['total_leads']);
    }

    public function test_it_calculates_opportunity_and_revenue_metrics_correctly(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stageProposal = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
            'probability' => 50,
            'is_won' => false,
            'is_lost' => false,
        ]);
        $stageWon = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
            'probability' => 100,
            'is_won' => true,
            'is_lost' => false,
        ]);
        $stageLost = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 3,
            'probability' => 0,
            'is_won' => false,
            'is_lost' => true,
        ]);

        // Deal Open: 100.000.000, prob 50% => weighted = 50.000.000
        Opportunity::factory()->create([
            'owner_id' => $this->userA->id,
            'department_id' => $this->deptA->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageProposal->id,
            'amount' => 100000000,
            'is_won' => false,
            'is_lost' => false,
            'created_at' => now()->subDays(10),
        ]);

        // Deal Won: 200.000.000, actual close date = now() (cycle 10 ngày)
        $createdAt = now()->subDays(10)->startOfDay();
        $closedAt = now()->startOfDay();

        Opportunity::factory()->create([
            'owner_id' => $this->userA->id,
            'department_id' => $this->deptA->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageWon->id,
            'amount' => 200000000,
            'is_won' => true,
            'is_lost' => false,
            'created_at' => $createdAt,
            'actual_close_date' => $closedAt,
        ]);

        // Deal Lost: 50.000.000
        Opportunity::factory()->create([
            'owner_id' => $this->userA->id,
            'department_id' => $this->deptA->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageLost->id,
            'amount' => 50000000,
            'is_won' => false,
            'is_lost' => true,
            'lost_reason' => 'Giá cao quá',
            'created_at' => now()->subDays(5),
            'actual_close_date' => now(),
        ]);

        $filters = new ReportFilterData(datePreset: 'this_month', pipelineId: $pipeline->id);
        $metrics = $this->service->getOpportunityMetrics($this->admin, $filters);

        $this->assertEquals(3, $metrics['total_opportunities']);
        $this->assertEquals(1, $metrics['open_opportunities']);
        $this->assertEquals(1, $metrics['won_opportunities']);
        $this->assertEquals(1, $metrics['lost_opportunities']);
        $this->assertEquals(350000000, $metrics['total_amount']);
        $this->assertEquals(200000000, $metrics['won_amount']);
        // Weighted = 100M*0.5 + 200M*1.0 + 50M*0 = 50M + 200M = 250M
        $this->assertEquals(250000000, $metrics['weighted_forecast']);
        // Win rate = 1 won / (1 won + 1 lost) = 50%
        $this->assertEquals(50.0, $metrics['win_rate']);
        $this->assertEquals(10.0, $metrics['avg_sales_cycle_days']);
        $this->assertArrayHasKey('Giá cao quá', $metrics['loss_reasons']);
    }

    public function test_it_calculates_funnel_metrics_correctly(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stage1 = PipelineStage::factory()->create(['pipeline_id' => $pipeline->id, 'position' => 1, 'name' => 'Tiếp cận']);
        $stage2 = PipelineStage::factory()->create(['pipeline_id' => $pipeline->id, 'position' => 2, 'name' => 'Báo giá']);

        Opportunity::factory()->create([
            'owner_id' => $this->userA->id,
            'department_id' => $this->deptA->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage1->id,
            'amount' => 50000000,
            'created_at' => now(),
        ]);

        $filters = new ReportFilterData(datePreset: 'this_month', pipelineId: $pipeline->id);
        $funnel = $this->service->getFunnelMetrics($this->admin, $filters);

        $this->assertCount(2, $funnel);
        $this->assertEquals('Tiếp cận', $funnel[0]['stage_name']);
        $this->assertEquals(1, $funnel[0]['opportunity_count']);
        $this->assertEquals(50000000, $funnel[0]['total_amount']);
        $this->assertEquals(0, $funnel[1]['opportunity_count']);
    }

    public function test_it_calculates_activity_and_task_metrics_correctly(): void
    {
        $company = Company::factory()->create();

        // 2 Activities cho UserA
        Activity::factory()->create([
            'user_id' => $this->userA->id,
            'activity_type' => ActivityType::Call->value,
            'subject_type' => Company::class,
            'subject_id' => $company->id,
            'created_at' => now(),
        ]);
        Activity::factory()->create([
            'user_id' => $this->userA->id,
            'activity_type' => ActivityType::Meeting->value,
            'subject_type' => Company::class,
            'subject_id' => $company->id,
            'created_at' => now(),
        ]);

        // Tasks cho UserA: 1 completed, 1 overdue
        Task::query()->create([
            'title' => 'Task completed',
            'assigned_to' => $this->userA->id,
            'created_by' => $this->userA->id,
            'status' => TaskStatus::Completed->value,
            'priority' => TaskPriority::High->value,
            'completed_at' => now(),
            'created_at' => now(),
        ]);
        Task::query()->create([
            'title' => 'Task overdue',
            'assigned_to' => $this->userA->id,
            'created_by' => $this->userA->id,
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::Medium->value,
            'due_date' => now()->subDays(2),
            'created_at' => now(),
        ]);

        $filters = new ReportFilterData(datePreset: 'this_month');
        $metrics = $this->service->getActivityAndTaskMetrics($this->userA, $filters);

        $this->assertEquals(2, $metrics['total_activities']);
        $this->assertEquals(2, $metrics['total_tasks']);
        $this->assertEquals(1, $metrics['completed_tasks']);
        $this->assertEquals(1, $metrics['overdue_tasks']);
    }

    public function test_overview_summary_aggregates_all_metric_groups(): void
    {
        $filters = new ReportFilterData(datePreset: 'this_month');
        $summary = $this->service->getOverviewSummary($this->admin, $filters);

        $this->assertArrayHasKey('lead_metrics', $summary);
        $this->assertArrayHasKey('opportunity_metrics', $summary);
        $this->assertArrayHasKey('activity_task_metrics', $summary);
    }
}
