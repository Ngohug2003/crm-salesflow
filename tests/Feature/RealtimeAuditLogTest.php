<?php

use App\Broadcasting\AuditLogsChannel;
use App\Events\AuditLogCreated;
use App\Livewire\AuditLogs\AuditLogList;
use App\Models\Department;
use App\Models\User;
use App\Services\SystemAuditService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function realtimeAuditUser(string $role, Department $department): User
{
    $user = User::factory()->create([
        'department_id' => $department->getKey(),
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('broadcasts only the new audit identifier on a private channel', function (): void {
    Event::fake([AuditLogCreated::class]);

    $department = Department::factory()->create();
    $actor = realtimeAuditUser('super-admin', $department);
    $target = realtimeAuditUser('sales', $department);

    app(SystemAuditService::class)->record(
        $actor,
        $target,
        'updated',
        'Realtime audit test',
        ['secret' => 'old'],
        ['name' => 'New'],
    );

    $activity = Activity::query()->sole();

    Event::assertDispatched(AuditLogCreated::class, function (AuditLogCreated $event) use ($activity): bool {
        $channels = $event->broadcastOn();

        return $event->activityId === $activity->getKey()
            && $event->broadcastAs() === 'audit.created'
            && $event->broadcastWith() === ['activity_id' => $activity->getKey()]
            && count($channels) === 1
            && $channels[0] instanceof PrivateChannel
            && $channels[0]->name === 'private-audit-logs';
    });
});

it('authorizes the audit private channel for IT admins', function (): void {
    $it = Department::factory()->create(['code' => 'IT']);
    $itAdmin = realtimeAuditUser('admin', $it);
    expect(Gate::forUser($itAdmin)->allows('viewAny', Activity::class))->toBeTrue();
    expect(app(AuditLogsChannel::class)->join($itAdmin))->toBeTrue();
});

it('authorizes the audit private channel for super admins', function (): void {
    $sales = Department::factory()->create(['code' => 'SALES']);
    $superAdmin = realtimeAuditUser('super-admin', $sales);

    expect(Gate::forUser($superAdmin)->allows('viewAny', Activity::class))->toBeTrue();

    expect(app(AuditLogsChannel::class)->join($superAdmin))->toBeTrue();
});

it('denies the audit private channel to admins outside IT', function (): void {
    $sales = Department::factory()->create(['code' => 'SALES']);
    $regularAdmin = realtimeAuditUser('admin', $sales);

    expect(Gate::forUser($regularAdmin)->denies('viewAny', Activity::class))->toBeTrue();

    expect(app(AuditLogsChannel::class)->join($regularAdmin))->toBeFalse();
});

it('refreshes the Livewire list and highlights the realtime activity', function (): void {
    $it = Department::factory()->create(['code' => 'IT']);
    $itAdmin = realtimeAuditUser('admin', $it);
    $target = realtimeAuditUser('sales', $it);

    app(SystemAuditService::class)->record($itAdmin, $target, 'updated', 'Hoạt động realtime mới', null, ['name' => 'New']);
    $activity = Activity::query()->sole();

    Livewire::actingAs($itAdmin)
        ->test(AuditLogList::class)
        ->dispatch('echo-private:audit-logs,.audit.created', ['activity_id' => $activity->getKey()])
        ->assertSet('latestRealtimeActivityId', $activity->getKey())
        ->assertSet('realtimeNotice', 'Đã nhận hoạt động mới qua kết nối realtime.')
        ->assertSee('Hoạt động realtime mới');
});
