<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\OpportunityFilterData;
use App\Enums\ForecastCategory;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Repositories\Contracts\OpportunityRepository;
use App\Services\OpportunityCloseWorkflowService;
use App\Services\OpportunityManagementService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ForecastCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Pipeline $pipeline;

    private PipelineStage $stage;

    private PipelineStage $wonStage;

    private PipelineStage $lostStage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->department = Department::factory()->create(['name' => 'Phòng Kinh doanh']);
        $this->user = User::factory()->create([
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
        $this->user->assignRole('sales');

        $this->pipeline = Pipeline::factory()->create(['is_default' => true]);
        $this->stage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'is_won' => false,
            'is_lost' => false,
            'probability' => 50,
        ]);
        $this->wonStage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'is_won' => true,
            'is_lost' => false,
            'probability' => 100,
        ]);
        $this->lostStage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'is_won' => false,
            'is_lost' => true,
            'probability' => 0,
        ]);
    }

    public function test_opportunity_creation_sets_default_forecast_category(): void
    {
        $service = app(OpportunityManagementService::class);
        $opportunity = $service->create($this->user, [
            'title' => 'Thương vụ Dự báo 2026',
            'amount' => 50000000,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $this->assertEquals(ForecastCategory::Pipeline, $opportunity->forecast_category);
    }

    public function test_editing_opportunity_allows_changing_forecast_category(): void
    {
        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'forecast_category' => ForecastCategory::Pipeline,
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test('opportunities.opportunity-editor', ['opportunityId' => $opportunity->id])
            ->set('forecast_category', 'commit')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(ForecastCategory::Commit, $opportunity->fresh()->forecast_category);
    }

    public function test_closing_opportunity_updates_forecast_category_to_closed(): void
    {
        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'forecast_category' => ForecastCategory::Commit,
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $closeService = app(OpportunityCloseWorkflowService::class);
        $wonOpp = $closeService->closeWon($this->user, $opportunity->id);

        $this->assertEquals(ForecastCategory::Closed, $wonOpp->forecast_category);
    }

    public function test_opportunity_list_filters_by_forecast_category(): void
    {
        Opportunity::factory()->create([
            'title' => 'Thương vụ Cam kết',
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'forecast_category' => ForecastCategory::Commit,
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        Opportunity::factory()->create([
            'title' => 'Thương vụ Kịch bản Tốt',
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'forecast_category' => ForecastCategory::BestCase,
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $repository = app(OpportunityRepository::class);

        $commitResults = $repository->paginateVisible($this->user, new OpportunityFilterData(
            forecastCategory: 'commit',
        ));
        $this->assertCount(1, $commitResults);
        $this->assertEquals('Thương vụ Cam kết', $commitResults->first()->title);

        $this->actingAs($this->user);
        Livewire::test('opportunities.opportunity-list')
            ->set('forecastCategory', 'commit')
            ->assertSee('Thương vụ Cam kết')
            ->assertDontSee('Thương vụ Kịch bản Tốt');
    }
}
