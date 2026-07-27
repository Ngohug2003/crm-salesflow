<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\ScanSlaWarningsCommand;
use App\Livewire\Customers\CustomerSlaDashboard;
use App\Models\Company;
use App\Models\Department;
use App\Models\Lead;
use App\Models\User;
use App\Services\CustomerSlaService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CustomerSlaTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Department $dept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);
        $this->superAdmin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->superAdmin->assignRole('super-admin');
    }

    public function test_sla_service_calculates_on_track_and_breached_status(): void
    {
        $service = app(CustomerSlaService::class);

        // Lead created just now -> On Track (target 24h)
        $newLead = Lead::factory()->create([
            'owner_id' => $this->superAdmin->id,
            'created_at' => now(),
        ]);

        $slaNew = $service->getSlaInfo($newLead);
        $this->assertEquals('on_track', $slaNew['status']);

        // Lead created 48 hours ago -> Breached (>24h target)
        $oldLead = Lead::factory()->create([
            'owner_id' => $this->superAdmin->id,
            'created_at' => now()->subHours(48),
        ]);

        $slaOld = $service->getSlaInfo($oldLead);
        $this->assertEquals('breached', $slaOld['status']);
    }

    public function test_customer_sla_dashboard_livewire_component_renders(): void
    {
        $this->actingAs($this->superAdmin);

        Company::factory()->create([
            'name' => 'Công ty Cổ phần Công nghệ SLA',
            'owner_id' => $this->superAdmin->id,
        ]);

        Livewire::test(CustomerSlaDashboard::class)
            ->assertOk()
            ->assertSee('Công ty Cổ phần Công nghệ SLA')
            ->set('slaStatus', 'all')
            ->assertOk();
    }

    public function test_scan_sla_warnings_artisan_command_executes_successfully(): void
    {
        $this->artisan(ScanSlaWarningsCommand::class)
            ->assertSuccessful();
    }
}
