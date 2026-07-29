<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadRoutingRule;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\LeadRoutingService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\LeadTaxonomySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LeadAutoRoutingTest extends TestCase
{
    use RefreshDatabase;

    private LeadRoutingService $routingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(LeadTaxonomySeeder::class);

        $this->routingService = app(LeadRoutingService::class);
    }

    public function test_it_routes_leads_using_round_robin_between_active_sales_reps(): void
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

        $rule = LeadRoutingRule::query()->create([
            'name' => 'Quy tắc Xoay vòng Sales',
            'strategy' => 'round_robin',
            'priority' => 1,
            'is_active' => true,
            'department_id' => $salesDept->id,
        ]);

        $lead1 = Lead::factory()->create(['owner_id' => null]);
        $lead2 = Lead::factory()->create(['owner_id' => null]);
        $lead3 = Lead::factory()->create(['owner_id' => null]);

        $exec1 = $this->routingService->routeLead($lead1);
        $exec2 = $this->routingService->routeLead($lead2);
        $exec3 = $this->routingService->routeLead($lead3);

        self::assertSame('success', $exec1->status);
        self::assertSame('success', $exec2->status);
        self::assertSame('success', $exec3->status);

        // Owners should rotate
        self::assertNotEquals($lead1->fresh()->owner_id, $lead2->fresh()->owner_id);
        self::assertEquals($lead1->fresh()->owner_id, $lead3->fresh()->owner_id);
    }

    public function test_it_filters_candidates_by_lead_source_condition(): void
    {
        $salesDept = Department::query()->where('code', 'SALES')->firstOrFail();
        $sourceWeb = LeadSource::query()->firstOrCreate(['name' => 'Website'], ['code' => 'WEB']);

        $sale = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
            'department_id' => $salesDept->id,
        ]);
        $sale->assignRole('sales');

        $rule = LeadRoutingRule::query()->create([
            'name' => 'Chỉ gán Lead từ Website',
            'strategy' => 'round_robin',
            'priority' => 1,
            'is_active' => true,
            'department_id' => $salesDept->id,
        ]);
        $rule->conditions()->create(['lead_source_id' => $sourceWeb->id]);

        $leadWeb = Lead::factory()->create([
            'owner_id' => null,
            'lead_source_id' => $sourceWeb->id,
        ]);

        $leadOther = Lead::factory()->create([
            'owner_id' => null,
            'lead_source_id' => null,
        ]);

        $execWeb = $this->routingService->routeLead($leadWeb);
        $execOther = $this->routingService->routeLead($leadOther);

        self::assertSame('success', $execWeb->status);
        self::assertSame($sale->id, $leadWeb->fresh()->owner_id);

        self::assertSame('unassigned_pool', $execOther->status);
        self::assertNull($leadOther->fresh()->owner_id);
    }
}
