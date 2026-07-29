<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadRoutingRule;
use App\Models\User;
use App\Services\LeadRoutingService;
use App\Services\LeadSlaService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\LeadTaxonomySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LeadSlaReassignTest extends TestCase
{
    use RefreshDatabase;

    private LeadSlaService $slaService;

    private LeadRoutingService $routingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(LeadTaxonomySeeder::class);

        $this->slaService = app(LeadSlaService::class);
        $this->routingService = app(LeadRoutingService::class);
    }

    public function test_it_calculates_sla_deadline_within_business_hours(): void
    {
        $fridayAfternoon = now()->next('Friday')->setTime(16, 0);
        $deadline = $this->slaService->calculateSlaDeadline($fridayAfternoon, 2);

        // 1.5h on Friday + 0.5h on Monday -> Monday 08:30
        self::assertSame('Monday', $deadline->format('l'));
        self::assertSame('08:30', $deadline->format('H:i'));
    }

    public function test_it_satisfies_sla_when_activity_is_created(): void
    {
        $salesDept = Department::query()->where('code', 'SALES')->firstOrFail();
        $sale = User::factory()->create(['department_id' => $salesDept->id]);

        $lead = Lead::factory()->create([
            'owner_id' => $sale->id,
            'sla_first_touch_due_at' => now()->addHours(2),
            'sla_satisfied_at' => null,
            'is_sla_overdue' => false,
        ]);

        self::assertFalse($this->slaService->checkAndSatisfySla($lead));

        Activity::factory()->create([
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'user_id' => $sale->id,
        ]);

        self::assertTrue($this->slaService->checkAndSatisfySla($lead));
        self::assertNotNull($lead->fresh()->sla_satisfied_at);
        self::assertFalse($lead->fresh()->is_sla_overdue);
    }

    public function test_it_auto_reassigns_lead_when_sla_is_overdue(): void
    {
        $salesDept = Department::query()->where('code', 'SALES')->firstOrFail();

        $sale1 = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
            'department_id' => $salesDept->id,
        ]);
        $sale1->assignRole('sales');

        $sale2 = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
            'department_id' => $salesDept->id,
        ]);
        $sale2->assignRole('sales');

        LeadRoutingRule::query()->create([
            'name' => 'Rule Xoay vòng',
            'strategy' => 'round_robin',
            'priority' => 1,
            'is_active' => true,
            'department_id' => $salesDept->id,
        ]);

        $lead = Lead::factory()->create([
            'owner_id' => $sale1->id,
            'sla_first_touch_due_at' => now()->subMinutes(10),
            'sla_satisfied_at' => null,
            'is_sla_overdue' => false,
        ]);

        $this->artisan('salesflow:check-lead-sla')
            ->assertExitCode(0);

        $freshLead = $lead->fresh();

        self::assertSame($sale2->id, $freshLead->owner_id);
        self::assertTrue($freshLead->sla_first_touch_due_at->gt(now()));
    }
}
