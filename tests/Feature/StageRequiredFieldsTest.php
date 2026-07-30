<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\StageRequirementsUnfulfilledException;
use App\Models\Company;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\OpportunityStageTransitionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StageRequiredFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Department $dept;

    private Pipeline $pipeline;

    private PipelineStage $initialStage;

    private PipelineStage $proposalStage;

    private PipelineStage $negotiationStage;

    private PipelineStage $closingStage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);
        $this->superAdmin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $this->pipeline = Pipeline::query()->create(['name' => 'Standard Pipeline', 'code' => 'std_req', 'is_default' => true]);

        $this->initialStage = PipelineStage::query()->create(['pipeline_id' => $this->pipeline->id, 'name' => 'Tiếp cận', 'code' => 'init', 'position' => 1, 'probability' => 10]);
        $this->proposalStage = PipelineStage::query()->create(['pipeline_id' => $this->pipeline->id, 'name' => 'Báo giá', 'code' => 'prop', 'position' => 2, 'probability' => 30]);
        $this->negotiationStage = PipelineStage::query()->create(['pipeline_id' => $this->pipeline->id, 'name' => 'Thương lượng', 'code' => 'nego', 'position' => 3, 'probability' => 50]);
        $this->closingStage = PipelineStage::query()->create(['pipeline_id' => $this->pipeline->id, 'name' => 'Chốt hợp đồng', 'code' => 'close', 'position' => 4, 'probability' => 80]);
    }

    public function test_blocks_transition_to_proposal_when_amount_is_zero(): void
    {
        $opp = Opportunity::factory()->create([
            'title' => 'Opportunity 0 VND',
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->initialStage->id,
            'amount' => 0,
            'owner_id' => $this->superAdmin->id,
        ]);

        $service = app(OpportunityStageTransitionService::class);

        $this->expectException(StageRequirementsUnfulfilledException::class);
        $this->expectExceptionMessage('Cơ hội bán hàng phải có Giá trị dự kiến (Doanh thu) lớn hơn 0 VNĐ');

        $service->transitionStage($this->superAdmin, $opp->id, $this->proposalStage->id);
    }

    public function test_blocks_transition_to_negotiation_when_expected_close_date_missing(): void
    {
        $opp = Opportunity::factory()->create([
            'title' => 'Opportunity missing close date',
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->proposalStage->id,
            'amount' => 100000000,
            'expected_close_date' => null,
            'owner_id' => $this->superAdmin->id,
        ]);

        $service = app(OpportunityStageTransitionService::class);

        $this->expectException(StageRequirementsUnfulfilledException::class);
        $this->expectExceptionMessage('Vui lòng cập nhật Ngày đóng dự kiến');

        $service->transitionStage($this->superAdmin, $opp->id, $this->negotiationStage->id);
    }

    public function test_allows_transition_when_all_required_fields_present(): void
    {
        $company = Company::factory()->create(['owner_id' => $this->superAdmin->id]);

        $opp = Opportunity::factory()->create([
            'title' => 'Valid Opportunity',
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->initialStage->id,
            'amount' => 100000000,
            'expected_close_date' => now()->addDays(15),
            'company_id' => $company->id,
            'owner_id' => $this->superAdmin->id,
        ]);

        $service = app(OpportunityStageTransitionService::class);

        $updated = $service->transitionStage($this->superAdmin, $opp->id, $this->closingStage->id);

        $this->assertEquals($this->closingStage->id, $updated->stage_id);
    }
}
