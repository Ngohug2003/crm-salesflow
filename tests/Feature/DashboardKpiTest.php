<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Livewire\Dashboard\DashboardOverview;
use App\Models\Department;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class DashboardKpiTest extends TestCase
{
    use RefreshDatabase;

    private Department $deptA;

    private User $admin;

    private User $userA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->deptA = Department::factory()->create(['name' => 'Phòng Kinh doanh A']);

        $this->admin = User::factory()->create([
            'department_id' => $this->deptA->id,
        ]);
        $this->admin->assignRole('admin');

        $this->userA = User::factory()->create([
            'department_id' => $this->deptA->id,
        ]);
        $this->userA->assignRole('sales');
    }

    public function test_it_renders_dashboard_overview_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeLivewire(DashboardOverview::class);
    }

    public function test_it_calculates_and_displays_kpi_metrics_on_dashboard(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stageWon = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'probability' => 100,
            'is_won' => true,
        ]);

        Opportunity::factory()->create([
            'owner_id' => $this->userA->id,
            'department_id' => $this->deptA->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageWon->id,
            'amount' => 150000000,
            'is_won' => true,
            'created_at' => now(),
            'actual_close_date' => now(),
        ]);

        Lead::factory()->create([
            'owner_id' => $this->userA->id,
            'department_id' => $this->deptA->id,
            'status' => LeadStatus::New->value,
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin);

        Livewire::test(DashboardOverview::class)
            ->assertOk()
            ->assertSee('150,000,000')
            ->assertSee('Khách hàng tiềm năng theo trạng thái');
    }

    public function test_it_updates_metrics_when_filters_change(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(DashboardOverview::class)
            ->set('datePreset', 'this_year')
            ->assertSet('datePreset', 'this_year')
            ->call('resetFilters')
            ->assertSet('datePreset', 'this_month');
    }
}
