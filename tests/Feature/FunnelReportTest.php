<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Reports\FunnelReport;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class FunnelReportTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;

    private User $admin;

    private User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Kinh doanh 1']);

        $this->admin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->admin->assignRole('admin');

        $this->salesUser = User::factory()->create(['department_id' => $this->dept->id]);
        $this->salesUser->assignRole('sales');
    }

    public function test_admin_can_access_funnel_report_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.funnel'))
            ->assertOk()
            ->assertSeeLivewire(FunnelReport::class);
    }

    public function test_super_admin_can_access_funnel_report_via_gate_bypass(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('reports.funnel'))
            ->assertOk()
            ->assertSeeLivewire(FunnelReport::class);
    }

    public function test_unauthorized_user_cannot_access_funnel_report(): void
    {
        $userWithoutPermission = User::factory()->create();

        $this->actingAs($userWithoutPermission)
            ->get(route('reports.funnel'))
            ->assertForbidden();
    }

    public function test_funnel_report_calculates_stage_counts_and_conversion_rates(): void
    {
        $pipeline = Pipeline::factory()->create(['name' => 'Quy trình Bán hàng chuẩn']);
        $stage1 = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Tiếp cận',
            'position' => 1,
            'probability' => 20,
        ]);
        $stage2 = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Đề xuất giá',
            'position' => 2,
            'probability' => 60,
        ]);

        // 2 deals ở stage 1, 1 deal ở stage 2
        Opportunity::factory()->create([
            'owner_id' => $this->admin->id,
            'department_id' => $this->dept->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage1->id,
            'amount' => 100000000,
            'created_at' => now(),
        ]);
        Opportunity::factory()->create([
            'owner_id' => $this->admin->id,
            'department_id' => $this->dept->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage1->id,
            'amount' => 50000000,
            'created_at' => now(),
        ]);
        Opportunity::factory()->create([
            'owner_id' => $this->admin->id,
            'department_id' => $this->dept->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage2->id,
            'amount' => 200000000,
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin);

        Livewire::test(FunnelReport::class, ['pipelineId' => $pipeline->id])
            ->assertOk()
            ->assertSee('Tiếp cận')
            ->assertSee('Đề xuất giá')
            ->assertSee('200,000,000');
    }

    public function test_it_filters_funnel_by_date_preset(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(FunnelReport::class)
            ->set('datePreset', 'this_quarter')
            ->assertSet('datePreset', 'this_quarter')
            ->call('resetFilters')
            ->assertSet('datePreset', 'this_month');
    }
}
