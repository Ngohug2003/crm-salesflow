<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Reports\ReportDrillDownModal;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\ReportDrillDownService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ReportDrillDownTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Department $dept;

    private Opportunity $wonOpportunity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);
        $this->superAdmin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $pipeline = Pipeline::query()->create(['name' => 'Drill Pipeline', 'code' => 'drill_pipe', 'is_default' => true]);
        $stageWon = PipelineStage::query()->create(['pipeline_id' => $pipeline->id, 'name' => 'Chốt Won', 'code' => 'won_drill', 'position' => 1, 'is_won' => true]);

        $this->wonOpportunity = Opportunity::factory()->create([
            'title' => 'Hợp đồng phần mềm CRM Vinamilk',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageWon->id,
            'amount' => 500000000,
            'is_won' => true,
            'owner_id' => $this->superAdmin->id,
        ]);
    }

    public function test_service_returns_drill_down_won_opportunities(): void
    {
        $service = app(ReportDrillDownService::class);

        $data = $service->getDrillDownData($this->superAdmin, 'won_opportunities');

        $this->assertEquals('won_opportunities', $data['type']);
        $this->assertNotEmpty($data['rows']);
        $this->assertEquals('Hợp đồng phần mềm CRM Vinamilk', $data['rows'][0]['name']);
    }

    public function test_report_drill_down_modal_livewire_component_renders(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ReportDrillDownModal::class)
            ->assertOk()
            ->call('open', 'won_opportunities')
            ->assertOk()
            ->assertSee('Hợp đồng phần mềm CRM Vinamilk');
    }
}
