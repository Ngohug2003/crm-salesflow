<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Department;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadScoringService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

final class LeadScoringTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->department = Department::factory()->create([
            'name' => 'Phòng Sales Thăng Long',
            'code' => 'SALES_TL',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Kinh Doanh 01',
            'email' => 'kd01@salesflow.test',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
        $this->user->assignRole('sales');
    }

    public function test_lead_score_calculated_automatically_on_saving(): void
    {
        $lead = Lead::factory()->create([
            'full_name' => 'Nguyễn Thị Hot',
            'email' => 'hot@company.com',
            'phone' => '0988888888',
            'company_name' => 'Công Ty Công Nghệ A',
            'job_title' => 'Giám đốc',
            'city' => 'Hà Nội',
            'estimated_value' => 50000000,
            'status' => LeadStatus::Qualified,
            'priority' => LeadPriority::High,
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $this->assertGreaterThanOrEqual(70, $lead->score);
        $this->assertEquals('hot', $lead->score_level);
        $this->assertEquals('emerald', $lead->score_badge_color);
        $this->assertEquals('Hot Lead', $lead->score_level_label);
    }

    public function test_stale_lead_receives_penalty_deduction(): void
    {
        $lead = Lead::factory()->create([
            'full_name' => 'Phạm Văn Stale',
            'email' => null,
            'phone' => null,
            'secondary_phone' => null,
            'company_name' => null,
            'job_title' => null,
            'address' => null,
            'city' => null,
            'province' => null,
            'country' => null,
            'estimated_value' => null,
            'status' => LeadStatus::New,
            'priority' => LeadPriority::Low,
            'created_at' => Carbon::now()->subDays(40),
        ]);

        $service = app(LeadScoringService::class);
        $score = $service->calculateScore($lead);

        $this->assertEquals(0, $score); // Penalized to minimum 0
        $this->assertEquals('cold', $lead->score_level);
    }

    public function test_lead_list_can_filter_and_sort_by_score(): void
    {
        $hotLead = Lead::factory()->create([
            'full_name' => 'Hot Prospect Person',
            'email' => 'hot@prospect.com',
            'phone' => '0901234567',
            'company_name' => 'Tập đoàn B',
            'status' => LeadStatus::Qualified,
            'priority' => LeadPriority::High,
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $coldLead = Lead::factory()->create([
            'full_name' => 'Cold Inactive Target',
            'email' => null,
            'phone' => null,
            'company_name' => null,
            'job_title' => null,
            'status' => LeadStatus::Unqualified,
            'priority' => LeadPriority::Low,
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test('leads.lead-list')
            ->set('scoreLevel', 'hot')
            ->assertSee('Hot Prospect Person')
            ->assertDontSee('Cold Inactive Target');

        Livewire::test('leads.lead-list')
            ->set('sort', 'score')
            ->set('direction', 'desc')
            ->assertSeeInOrder(['Hot Prospect Person', 'Cold Inactive Target']);
    }
}
