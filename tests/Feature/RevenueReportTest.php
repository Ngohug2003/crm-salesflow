<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Reports\RevenueReport;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class RevenueReportTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;

    private User $admin;

    private User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Kinh doanh 2']);

        $this->admin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->admin->assignRole('admin');

        $this->salesUser = User::factory()->create(['department_id' => $this->dept->id]);
        $this->salesUser->assignRole('sales');
    }

    public function test_admin_can_access_revenue_report_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.revenue'))
            ->assertOk()
            ->assertSeeLivewire(RevenueReport::class);
    }

    public function test_super_admin_can_access_revenue_report_via_gate_bypass(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('reports.revenue'))
            ->assertOk()
            ->assertSeeLivewire(RevenueReport::class);
    }

    public function test_unauthorized_user_cannot_access_revenue_report(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->get(route('reports.revenue'))
            ->assertForbidden();
    }

    public function test_revenue_report_calculates_won_open_forecast_and_loss_reasons(): void
    {
        $pipeline = Pipeline::factory()->create(['name' => 'Quy trình Doanh thu']);
        $stageProposal = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Báo giá',
            'probability' => 50,
            'is_won' => false,
            'is_lost' => false,
        ]);
        $stageWon = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Thành công',
            'probability' => 100,
            'is_won' => true,
            'is_lost' => false,
        ]);
        $stageLost = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Thất bại',
            'probability' => 0,
            'is_won' => false,
            'is_lost' => true,
        ]);

        // Deal Won: 300M
        Opportunity::factory()->create([
            'owner_id' => $this->admin->id,
            'department_id' => $this->dept->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageWon->id,
            'amount' => 300000000,
            'is_won' => true,
            'is_lost' => false,
            'created_at' => now(),
            'actual_close_date' => now(),
        ]);

        // Deal Open: 100M (prob 50% => weighted 50M)
        Opportunity::factory()->create([
            'owner_id' => $this->admin->id,
            'department_id' => $this->dept->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageProposal->id,
            'amount' => 100000000,
            'is_won' => false,
            'is_lost' => false,
            'created_at' => now(),
            'expected_close_date' => now(),
        ]);

        // Deal Lost: 50M (Lý do: Không đủ ngân sách)
        Opportunity::factory()->create([
            'owner_id' => $this->admin->id,
            'department_id' => $this->dept->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageLost->id,
            'amount' => 50000000,
            'is_won' => false,
            'is_lost' => true,
            'lost_reason' => 'Không đủ ngân sách',
            'created_at' => now(),
            'actual_close_date' => now(),
        ]);

        $this->actingAs($this->admin);

        Livewire::test(RevenueReport::class, ['pipelineId' => $pipeline->id])
            ->assertOk()
            ->assertSee('300,000,000')
            ->assertSee('100,000,000')
            ->assertSee('50,000,000')
            ->assertSee('Lý do thất bại');
    }
}
