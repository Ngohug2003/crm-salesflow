<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Platform\SystemConsole;
use App\Models\Department;
use App\Models\User;
use App\Services\Platform\EnvironmentReadinessCheckService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class EnvironmentReadinessTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $dept = Department::factory()->create(['name' => 'Phòng CNTT']);

        $this->superAdmin = User::factory()->create(['department_id' => $dept->id]);
        $this->superAdmin->assignRole('super-admin');
    }

    public function test_environment_readiness_service_checks_all_items(): void
    {
        $service = app(EnvironmentReadinessCheckService::class);
        $report = $service->checkAll();

        $this->assertArrayHasKey('overall_status', $report);
        $this->assertArrayHasKey('items', $report);
        $this->assertCount(9, $report['items']);

        $itemKeys = array_column($report['items'], 'key');
        $this->assertContains('app_key', $itemKeys);
        $this->assertContains('app_debug', $itemKeys);
        $this->assertContains('app_timezone', $itemKeys);
        $this->assertContains('database_config', $itemKeys);
        $this->assertContains('mail_config', $itemKeys);
        $this->assertContains('queue_config', $itemKeys);
        $this->assertContains('reverb_config', $itemKeys);
        $this->assertContains('session_security', $itemKeys);
        $this->assertContains('storage_symlink', $itemKeys);
    }

    public function test_artisan_env_check_command_runs_successfully(): void
    {
        $this->artisan('app:env-check')
            ->assertExitCode(0);
    }

    public function test_livewire_system_console_renders_environment_readiness_checklist(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(SystemConsole::class)
            ->assertOk()
            ->assertSee('Environment Readiness Checklist')
            ->assertSee('Khóa ứng dụng (APP_KEY)')
            ->assertSee('Môi trường & Chế độ Debug')
            ->assertSee('Múi giờ hệ thống (Timezone)')
            ->assertSee('Cấu hình Cơ sở dữ liệu')
            ->assertSee('Liên kết thư mục Public Storage');
    }
}
