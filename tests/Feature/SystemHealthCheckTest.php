<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Platform\SystemConsole;
use App\Models\Department;
use App\Models\User;
use App\Services\Platform\SystemHealthCheckService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SystemHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $itAdmin;

    private User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $dept = Department::factory()->create(['name' => 'Phòng CNTT']);

        $this->superAdmin = User::factory()->create(['department_id' => $dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $this->itAdmin = User::factory()->create(['department_id' => $dept->id]);
        $this->itAdmin->assignRole('admin');

        $this->salesUser = User::factory()->create(['department_id' => $dept->id]);
        $this->salesUser->assignRole('sales');
    }

    public function test_health_check_service_returns_all_six_services(): void
    {
        $service = app(SystemHealthCheckService::class);
        $report = $service->checkAll();

        $this->assertArrayHasKey('status', $report);
        $this->assertArrayHasKey('timestamp', $report);
        $this->assertArrayHasKey('services', $report);

        $services = $report['services'];
        $this->assertArrayHasKey('database', $services);
        $this->assertArrayHasKey('redis', $services);
        $this->assertArrayHasKey('queue', $services);
        $this->assertArrayHasKey('realtime', $services);
        $this->assertArrayHasKey('storage', $services);
        $this->assertArrayHasKey('app', $services);

        $this->assertEquals('PostgreSQL Database', $services['database']['name']);
        $this->assertGreaterThanOrEqual(0, $services['database']['latency_ms']);
    }

    public function test_api_endpoint_allows_authorized_users_and_denies_unauthorized(): void
    {
        // Guests redirect to login
        $this->getJson(route('system-console.health-api'))
            ->assertUnauthorized();

        // Sales user forbidden 403
        $this->actingAs($this->salesUser)
            ->getJson(route('system-console.health-api'))
            ->assertForbidden();

        // IT Admin returns 200 OK with JSON structure
        $this->actingAs($this->itAdmin)
            ->getJson(route('system-console.health-api'))
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database',
                    'redis',
                    'queue',
                    'realtime',
                    'storage',
                    'app',
                ],
            ]);
    }

    public function test_livewire_system_console_renders_health_check_section(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(SystemConsole::class)
            ->assertOk()
            ->assertSee('Kiểm tra sức khỏe hệ thống')
            ->assertSee('PostgreSQL Database')
            ->assertSee('Redis Cache')
            ->assertSee('Queue Workers')
            ->assertSee('Reverb WebSocket')
            ->assertSee('Storage Disks')
            ->assertSee('Runtime Environment');
    }
}
