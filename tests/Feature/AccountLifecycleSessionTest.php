<?php

declare(strict_types=1);

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use App\Services\UserManagementService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function createActiveUser(string $email, string $role = 'sales'): User
{
    $user = User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

function insertSession(string $id, User $user): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Symfony',
        'payload' => base64_encode('payload'),
        'last_activity' => time(),
    ]);
}

it('revokes all database sessions when an administrator blocks a user', function (): void {
    $admin = createActiveUser('admin@salesflow.test', 'super-admin');
    $target = createActiveUser('target@salesflow.test', 'sales');

    insertSession('session-1', $target);
    insertSession('session-2', $target);
    insertSession('session-admin', $admin);

    expect(DB::table('sessions')->where('user_id', $target->getKey())->count())->toBe(2);

    app(UserManagementService::class)->save($admin, $target, [
        'name' => $target->name,
        'email' => $target->email,
        'department_id' => $target->department_id,
        'is_active' => false,
        'roles' => ['sales'],
    ]);

    expect(DB::table('sessions')->where('user_id', $target->getKey())->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $admin->getKey())->count())->toBe(1);

    // Verify audit log
    $audit = Activity::query()->where('event', 'updated')->latest('id')->firstOrFail();
    expect(data_get($audit->properties->all(), 'new.is_active'))->toBeFalse()
        ->and(data_get($audit->properties->all(), 'revoked_sessions_count'))->toBe(2);
});

it('revokes other database sessions and updates remember token when a user changes their own password', function (): void {
    $user = createActiveUser('user@salesflow.test', 'sales');
    $oldToken = $user->remember_token;

    $this->actingAs($user);
    request()->setLaravelSession(app('session')->driver());
    request()->session()->start();
    $currentSessionId = request()->session()->getId();

    insertSession($currentSessionId, $user);
    insertSession('other-session-1', $user);
    insertSession('other-session-2', $user);

    app(UpdateUserPassword::class)->update($user, [
        'current_password' => 'password',
        'password' => 'NewSecurePassword123!',
        'password_confirmation' => 'NewSecurePassword123!',
    ]);

    expect(Hash::check('NewSecurePassword123!', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->remember_token)->not->toBe($oldToken)
        ->and(DB::table('sessions')->where('user_id', $user->getKey())->pluck('id')->all())->toBe([$currentSessionId]);

    $audit = Activity::query()->where('event', 'password-changed')->latest('id')->firstOrFail();
    expect(data_get($audit->properties->all(), 'revoked_sessions_count'))->toBe(2);
});

it('revokes all database sessions and updates remember token when an admin resets another users password', function (): void {
    $admin = createActiveUser('admin@salesflow.test', 'super-admin');
    $target = createActiveUser('target@salesflow.test', 'sales');
    $oldToken = $target->remember_token;

    insertSession('session-1', $target);
    insertSession('session-2', $target);
    insertSession('session-admin', $admin);

    app(UserManagementService::class)->save($admin, $target, [
        'name' => $target->name,
        'email' => $target->email,
        'department_id' => $target->department_id,
        'is_active' => true,
        'roles' => ['sales'],
        'password' => 'NewSecurePassword123!',
    ]);

    $freshToken = $target->fresh()->remember_token;

    expect(Hash::check('NewSecurePassword123!', $target->fresh()->password))->toBeTrue()
        ->and($freshToken)->not->toBe($oldToken)
        ->and(DB::table('sessions')->where('user_id', $target->getKey())->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $admin->getKey())->count())->toBe(1);

    $audit = Activity::query()->where('event', 'updated')->latest('id')->firstOrFail();
    expect(data_get($audit->properties->all(), 'revoked_sessions_count'))->toBe(2);
});

it('revokes all database sessions and updates remember token during forgot password reset flow', function (): void {
    $user = createActiveUser('forgot@salesflow.test', 'sales');
    $oldToken = $user->remember_token;

    insertSession('session-a', $user);
    insertSession('session-b', $user);

    app(ResetUserPassword::class)->reset($user, [
        'password' => 'NewResetPassword123!',
        'password_confirmation' => 'NewResetPassword123!',
    ]);

    expect(Hash::check('NewResetPassword123!', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->remember_token)->not->toBe($oldToken)
        ->and(DB::table('sessions')->where('user_id', $user->getKey())->count())->toBe(0);

    $audit = Activity::query()->where('event', 'password-reset')->latest('id')->firstOrFail();
    expect(data_get($audit->properties->all(), 'revoked_sessions_count'))->toBe(2);
});
