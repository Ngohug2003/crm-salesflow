<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

/**
 * @return array<string, string>
 */
function checkpointAccounts(): array
{
    return [
        'super-admin' => 'admin@salesflow.test',
        'admin' => 'it.admin@salesflow.test',
        'sales-manager' => 'demo03@salesflow.test',
        'sales' => 'demo04@salesflow.test',
        'viewer' => 'demo12@salesflow.test',
    ];
}

function checkpointUser(string $role): User
{
    return User::query()
        ->where('email', checkpointAccounts()[$role])
        ->firstOrFail();
}

it('seeds one usable checkpoint account for every system role', function (): void {
    foreach (checkpointAccounts() as $role => $email) {
        $user = User::query()->where('email', $email)->firstOrFail();

        expect($user->hasRole($role))->toBeTrue()
            ->and($user->is_active)->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull();
    }

    expect(checkpointUser('admin')->department?->code)->toBe('IT');
});

it('enforces the page and navigation matrix for each system role', function (
    string $role,
    bool $canViewUsers,
    bool $canViewDepartments,
    bool $canViewAuditLogs,
): void {
    $user = checkpointUser($role);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('help.roles'))->assertOk();

    expect($this->actingAs($user)->get(route('users.index'))->status())
        ->toBe($canViewUsers ? 200 : 403)
        ->and($this->actingAs($user)->get(route('departments.index'))->status())
        ->toBe($canViewDepartments ? 200 : 403)
        ->and($this->actingAs($user)->get(route('audit-logs.index'))->status())
        ->toBe($canViewAuditLogs ? 200 : 403);

    $navigation = $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('dashboard').'"', false)
        ->assertSee('href="'.route('help.roles').'"', false);

    $canViewUsers
        ? $navigation->assertSee('href="'.route('users.index').'"', false)
        : $navigation->assertDontSee('href="'.route('users.index').'"', false);

    $canViewDepartments
        ? $navigation->assertSee('href="'.route('departments.index').'"', false)
        : $navigation->assertDontSee('href="'.route('departments.index').'"', false);

    $canViewAuditLogs
        ? $navigation->assertSee('href="'.route('audit-logs.index').'"', false)
        : $navigation->assertDontSee('href="'.route('audit-logs.index').'"', false);
})->with([
    'super-admin' => ['super-admin', true, true, true],
    'admin thuộc IT' => ['admin', true, true, true],
    'sales-manager' => ['sales-manager', true, true, false],
    'sales' => ['sales', false, false, false],
    'viewer' => ['viewer', false, false, false],
]);

it('enforces the backend action matrix independently from hidden navigation', function (
    string $role,
    bool $canManageUsers,
    bool $canManageDepartments,
): void {
    $actor = checkpointUser($role);
    $target = checkpointUser('sales');
    $department = Department::query()->where('code', 'SALES')->firstOrFail();

    expect(Gate::forUser($actor)->allows('create', User::class))->toBe($canManageUsers)
        ->and(Gate::forUser($actor)->allows('update', $target))->toBe($canManageUsers)
        ->and(Gate::forUser($actor)->allows('delete', $target))->toBe($canManageUsers)
        ->and(Gate::forUser($actor)->allows('create', Department::class))->toBe($canManageDepartments)
        ->and(Gate::forUser($actor)->allows('update', $department))->toBe($canManageDepartments)
        ->and(Gate::forUser($actor)->allows('delete', $department))->toBe($canManageDepartments);
})->with([
    'super-admin' => ['super-admin', true, true],
    'admin thuộc IT' => ['admin', true, true],
    'sales-manager' => ['sales-manager', false, false],
    'sales' => ['sales', false, false],
    'viewer' => ['viewer', false, false],
]);

it('restricts manager visibility to their department at policy level', function (): void {
    $manager = checkpointUser('sales-manager');
    $sameDepartment = checkpointUser('sales');
    $otherDepartment = User::query()->where('email', 'demo15@salesflow.test')->firstOrFail();

    expect(Gate::forUser($manager)->allows('view', $sameDepartment))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('view', $otherDepartment))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('update', $sameDepartment))->toBeFalse();
});

it('requires both the admin role and IT department for audit access', function (): void {
    $itAdmin = checkpointUser('admin');
    $regularAdmin = User::query()->where('email', 'demo01@salesflow.test')->firstOrFail();

    expect(Gate::forUser($itAdmin)->allows('viewAny', Activity::class))->toBeTrue()
        ->and(Gate::forUser($regularAdmin)->allows('viewAny', Activity::class))->toBeFalse();

    $this->actingAs($regularAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('href="'.route('audit-logs.index').'"', false);
});

it('never lets a regular admin update or delete a super admin', function (): void {
    $regularAdmin = checkpointUser('admin');
    $superAdmin = checkpointUser('super-admin');

    expect(Gate::forUser($regularAdmin)->allows('update', $superAdmin))->toBeFalse()
        ->and(Gate::forUser($regularAdmin)->allows('delete', $superAdmin))->toBeFalse();
});

it('enforces active and verified account state before protected pages', function (): void {
    $inactive = User::query()->where('email', 'demo08@salesflow.test')->firstOrFail();
    $unverified = User::query()->where('email', 'demo07@salesflow.test')->firstOrFail();

    $this->actingAs($inactive)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
    $this->assertGuest();

    $this->actingAs($unverified)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});
