<?php

use App\Livewire\Settings\SessionManager;
use App\Models\User;
use App\Services\SessionManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('requires login and shows only the current users sessions', function (): void {
    $user = sessionUser('sessions-owner@salesflow.test');
    $otherUser = sessionUser('sessions-other@salesflow.test');

    sessionRow('own-session-a', $user, '192.168.10.55');
    sessionRow('other-session-a', $otherUser, '10.10.10.20');

    $this->get(route('sessions.index'))->assertRedirect(route('login'));

    $this->actingAs($user)
        ->withSession([])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Phiên đăng nhập');

    Livewire::actingAs($user)
        ->withQueryParams([])
        ->test(SessionManager::class)
        ->assertOk();
});

it('revokes one owned session and rejects another users session token', function (): void {
    $user = sessionUser('sessions-revoke@salesflow.test');
    $otherUser = sessionUser('sessions-revoke-other@salesflow.test');

    sessionRow('owned-session-to-delete', $user, '203.0.113.48');
    sessionRow('foreign-session-to-keep', $otherUser, '198.51.100.91');

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => now()->unix()]);

    $token = Crypt::encryptString('owned-session-to-delete');

    $service = app(SessionManagementService::class);
    $result = $service->revoke($user, $token, 'fake-current-session-id');

    expect($result)->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'owned-session-to-delete')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'foreign-session-to-keep')->exists())->toBeTrue();

    expect(fn () => $service->revoke($user, Crypt::encryptString('foreign-session-to-keep'), 'fake-current-session-id'))
        ->toThrow(InvalidArgumentException::class);

    $auditJson = Activity::query()->where('event', 'session_revoked')->sole()->properties->toJson();

    expect($auditJson)->not->toContain('owned-session-to-delete', 'foreign-session-to-keep')
        ->and($auditJson)->toContain('fingerprint');
});

it('requires recent password confirmation before revoking sessions', function (): void {
    $user = sessionUser('sessions-password-confirm@salesflow.test');
    sessionRow('sensitive-session', $user, '192.0.2.15');

    Livewire::actingAs($user)
        ->test(SessionManager::class)
        ->call('revokeOthers')
        ->assertRedirect(route('password.confirm'));

    expect(DB::table('sessions')->where('id', 'sensitive-session')->exists())->toBeTrue();
});

it('revokes every other session while keeping the current session', function (): void {
    $user = sessionUser('sessions-revoke-others@salesflow.test');
    $currentSessionId = 'current-session-id';

    sessionRow($currentSessionId, $user, '192.168.1.10');
    sessionRow('old-session-a', $user, '192.168.1.11');
    sessionRow('old-session-b', $user, '192.168.1.12');

    $count = app(SessionManagementService::class)->revokeOthers($user, $currentSessionId);

    expect($count)->toBe(2)
        ->and(DB::table('sessions')->where('user_id', $user->getKey())->pluck('id')->all())->toBe([$currentSessionId]);

    $audit = Activity::query()->where('event', 'sessions_revoked')->first();

    expect($audit)->not->toBeNull()
        ->and($audit->properties->get('new')['revoked_sessions_count'])->toBe(2);
});

it('detects the current session and marks device metadata safely', function (): void {
    $user = sessionUser('sessions-current@salesflow.test');
    $currentSessionId = 'session-current-marker';

    sessionRow($currentSessionId, $user, '2001:db8:85a3::8a2e:370:7334');

    $sessions = app(SessionManagementService::class)->listFor($user, $currentSessionId);

    expect($sessions)->toHaveCount(1)
        ->and($sessions[0]->isCurrent)->toBeTrue()
        ->and($sessions[0]->browser)->toBe('Chrome')
        ->and($sessions[0]->platform)->toBe('Windows')
        ->and($sessions[0]->ipAddress)->toContain(':****')
        ->and($sessions[0]->fingerprint)->not->toBe($currentSessionId);
});

function sessionUser(string $email): User
{
    return User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
}

function sessionRow(string $id, User $user, string $ipAddress): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->getKey(),
        'ip_address' => $ipAddress,
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'payload' => base64_encode('test-session-payload'),
        'last_activity' => now()->timestamp,
    ]);
}
