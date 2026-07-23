<?php

use App\Livewire\Platform\SystemConsole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->consoleLogDirectory = storage_path('framework/testing/system-console');
    File::deleteDirectory($this->consoleLogDirectory);
    File::ensureDirectoryExists($this->consoleLogDirectory);
    config(['logging.channels.application.path' => $this->consoleLogDirectory.'/application.log']);
    Log::forgetChannel('application');

    File::put($this->consoleLogDirectory.'/application-2026-07-23.log', systemConsoleLogJson()."\n");
});

afterEach(function (): void {
    Log::forgetChannel('application');
    File::deleteDirectory($this->consoleLogDirectory);
});

it('allows admin and super admin while denying every sales role', function (): void {
    $admin = systemConsoleUser('admin');
    $superAdmin = systemConsoleUser('super-admin');

    $this->actingAs($admin)
        ->get(route('system-console.index'))
        ->assertOk()
        ->assertSee('System Console')
        ->assertSee('Toàn màn hình')
        ->assertSee('req-console-001');

    $this->actingAs($superAdmin)
        ->get(route('system-console.index'))
        ->assertOk();

    foreach (['sales-manager', 'sales', 'viewer'] as $role) {
        $this->actingAs(systemConsoleUser($role))
            ->get(route('system-console.index'))
            ->assertForbidden();
    }
});

it('requires authentication and only renders navigation for authorized roles', function (): void {
    $this->get(route('system-console.index'))->assertRedirect(route('login'));

    $this->actingAs(systemConsoleUser('admin'))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('System Console');

    $this->actingAs(systemConsoleUser('sales'))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('System Console');
});

it('polls filters pauses and reauthorizes every action', function (): void {
    $admin = systemConsoleUser('admin');

    $component = Livewire::actingAs($admin)
        ->test(SystemConsole::class)
        ->assertSet('paused', false)
        ->assertSee('req-console-001')
        ->set('search', 'not-found')
        ->assertSet('entries', [])
        ->call('clearFilters')
        ->assertSee('req-console-001')
        ->call('togglePolling')
        ->assertSet('paused', true)
        ->call('togglePolling')
        ->assertSet('paused', false);

    Role::findOrCreate('sales', 'web');
    $admin->syncRoles('sales');
    $component->call('refreshLogs')->assertForbidden();
});

function systemConsoleUser(string $role): User
{
    Role::findOrCreate($role, 'web');
    $user = User::factory()->create([
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $user->assignRole($role);

    return $user;
}

function systemConsoleLogJson(): string
{
    return json_encode([
        'message' => 'http_request_completed',
        'level_name' => 'DEBUG',
        'datetime' => '2026-07-23T08:00:00+07:00',
        'context' => ['status_code' => 200, 'duration_ms' => 5.2, 'password' => 'never-render-this'],
        'extra' => [
            'request_id' => 'req-console-001',
            'module' => 'dashboard',
            'action' => 'view',
            'user_id' => 1,
            'authorization' => 'never-render-this',
        ],
    ], JSON_THROW_ON_ERROR);
}
