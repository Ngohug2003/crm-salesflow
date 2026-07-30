<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Opportunities\OpportunityRiskDashboard;
use App\Models\Company;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\OpportunityRiskSnapshot;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\OpportunityRiskService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class OpportunityRiskDetectionTest extends TestCase
{
    use RefreshDatabase;

    private OpportunityRiskService $riskService;

    private User $adminUser;

    private PipelineStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $salesDept = Department::query()->where('code', 'SALES')->firstOrFail();

        $this->adminUser = User::factory()->create([
            'is_active' => true,
            'department_id' => $salesDept->id,
        ]);
        $this->adminUser->assignRole('admin');

        $pipeline = Pipeline::factory()->create();
        $this->stage = PipelineStage::factory()->create(['pipeline_id' => $pipeline->id, 'probability' => 50]);

        $this->riskService = app(OpportunityRiskService::class);
    }

    public function test_it_evaluates_at_risk_opportunity_due_to_inactivity_and_past_close_date(): void
    {
        $company = Company::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'title' => 'Dự án mua sắm thiết bị P10-04',
            'code' => 'OPP-P10-04',
            'amount' => 500000000,
            'pipeline_id' => $this->stage->pipeline_id,
            'stage_id' => $this->stage->id,
            'company_id' => $company->id,
            'contact_id' => null, // Risk factor: missing contact
            'expected_close_date' => now()->subDays(5)->format('Y-m-d'), // Risk factor: past close date
            'owner_id' => $this->adminUser->id,
            'department_id' => $this->adminUser->department_id,
            'created_at' => now()->subDays(10), // Risk factor: no activity > 7 days
            'updated_at' => now()->subDays(10),
            'is_won' => false,
            'is_lost' => false,
        ]);

        $snapshot = $this->riskService->evaluateOpportunity($opportunity);

        self::assertNotNull($snapshot);
        self::assertGreaterThanOrEqual(50, $snapshot->score);
        self::assertContains($snapshot->level, ['high', 'critical']);
        self::assertDatabaseHas('opportunity_risk_snapshots', [
            'opportunity_id' => $opportunity->id,
            'is_current' => true,
        ]);
        self::assertDatabaseHas('opportunity_risk_factors', [
            'opportunity_risk_snapshot_id' => $snapshot->id,
            'code' => 'PAST_EXPECTED_CLOSE_DATE',
        ]);
        self::assertDatabaseHas('opportunity_risk_factors', [
            'opportunity_risk_snapshot_id' => $snapshot->id,
            'code' => 'MISSING_PRIMARY_CONTACT',
        ]);
    }

    public function test_it_batch_evaluates_opportunities_via_artisan_command(): void
    {
        Opportunity::factory()->count(3)->create([
            'pipeline_id' => $this->stage->pipeline_id,
            'stage_id' => $this->stage->id,
            'owner_id' => $this->adminUser->id,
            'department_id' => $this->adminUser->department_id,
            'is_won' => false,
            'is_lost' => false,
        ]);

        $this->artisan('salesflow:detect-at-risk-deals')
            ->assertExitCode(0);

        self::assertSame(3, OpportunityRiskSnapshot::query()->where('is_current', true)->count());
    }

    public function test_it_records_risk_acknowledgement_by_manager(): void
    {
        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $this->stage->pipeline_id,
            'stage_id' => $this->stage->id,
            'owner_id' => $this->adminUser->id,
            'department_id' => $this->adminUser->department_id,
            'is_won' => false,
            'is_lost' => false,
        ]);

        $ack = $this->riskService->acknowledgeRisk($this->adminUser, $opportunity, 'in_progress', 'Đã hẹn gặp khách hàng xử lý thắc mắc báo giá.');

        self::assertDatabaseHas('opportunity_risk_acknowledgements', [
            'id' => $ack->id,
            'user_id' => $this->adminUser->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_risk_dashboard_renders_and_filters_by_risk_level(): void
    {
        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $this->stage->pipeline_id,
            'stage_id' => $this->stage->id,
            'owner_id' => $this->adminUser->id,
            'department_id' => $this->adminUser->department_id,
            'expected_close_date' => now()->subDays(2)->format('Y-m-d'),
            'is_won' => false,
            'is_lost' => false,
        ]);

        $this->riskService->evaluateOpportunity($opportunity);

        $this->actingAs($this->adminUser);

        Livewire::test(OpportunityRiskDashboard::class)
            ->assertStatus(200)
            ->set('selectedLevel', 'all')
            ->assertSee($opportunity->title)
            ->call('openAcknowledgeModal', $opportunity->id)
            ->set('ackStatus', 'resolved')
            ->set('ackNotes', 'Đã chốt xong các điều khoản khẩn cấp')
            ->call('saveAcknowledgement')
            ->assertHasNoErrors();

        self::assertDatabaseHas('opportunity_risk_acknowledgements', [
            'status' => 'resolved',
        ]);
    }
}
