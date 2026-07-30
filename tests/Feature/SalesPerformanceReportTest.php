<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\TaskStatus;
use App\Livewire\Reports\SalesPerformanceReport;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SalesPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;

    private User $admin;

    private User $sales1;

    private User $sales2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Phòng Kinh doanh 3']);

        $this->admin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->admin->assignRole('admin');

        $this->sales1 = User::factory()->create(['name' => 'Nguyễn Văn A', 'department_id' => $this->dept->id]);
        $this->sales1->assignRole('sales');

        $this->sales2 = User::factory()->create(['name' => 'Trần Thị B', 'department_id' => $this->dept->id]);
        $this->sales2->assignRole('sales');
    }

    public function test_admin_can_access_sales_performance_report_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.performance'))
            ->assertOk()
            ->assertSeeLivewire(SalesPerformanceReport::class);
    }

    public function test_super_admin_can_access_sales_performance_report_via_gate_bypass(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('reports.performance'))
            ->assertOk()
            ->assertSeeLivewire(SalesPerformanceReport::class);
    }

    public function test_unauthorized_user_cannot_access_sales_performance_report(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->get(route('reports.performance'))
            ->assertForbidden();
    }

    public function test_sales_performance_report_ranks_top_performers(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stageWon = PipelineStage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'is_won' => true,
        ]);

        $company = Company::factory()->create();

        // Sales 1 (A): 500M Won, 1 Activity, 1 Completed Task
        Opportunity::factory()->create([
            'owner_id' => $this->sales1->id,
            'department_id' => $this->dept->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageWon->id,
            'amount' => 500000000,
            'is_won' => true,
            'created_at' => now(),
            'actual_close_date' => now(),
        ]);

        Activity::factory()->create([
            'user_id' => $this->sales1->id,
            'activity_type' => ActivityType::Call->value,
            'subject_type' => Company::class,
            'subject_id' => $company->id,
            'created_at' => now(),
        ]);

        Task::query()->create([
            'title' => 'Tư vấn hợp đồng A',
            'assigned_to' => $this->sales1->id,
            'created_by' => $this->sales1->id,
            'status' => TaskStatus::Completed->value,
            'created_at' => now(),
        ]);

        // Sales 2 (B): 200M Won
        Opportunity::factory()->create([
            'owner_id' => $this->sales2->id,
            'department_id' => $this->dept->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageWon->id,
            'amount' => 200000000,
            'is_won' => true,
            'created_at' => now(),
            'actual_close_date' => now(),
        ]);

        $this->actingAs($this->admin);

        Livewire::test(SalesPerformanceReport::class)
            ->assertOk()
            ->assertSee('Nguyễn Văn A')
            ->assertSee('Trần Thị B')
            ->assertSee('500,000,000')
            ->assertSee('200,000,000');
    }
}
