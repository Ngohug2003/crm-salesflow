<?php

use App\Enums\DataScope;
use App\Livewire\Users\UserList;
use App\Models\Department;
use App\Models\User;
use App\Services\Authorization\DataScopeResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function roleAssignmentActor(string $role = 'admin', ?Department $department = null): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('shows assignable roles according to the actor privilege level', function (): void {
    $admin = roleAssignmentActor();
    $superAdmin = roleAssignmentActor('super-admin');

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openCreate')
        ->assertSee('Sales Manager')
        ->assertSee('Viewer')
        ->assertDontSee('wire:model="form.roles" value="super-admin"', false);

    Livewire::actingAs($superAdmin)
        ->test(UserList::class)
        ->call('openCreate')
        ->assertSee('wire:model="form.roles" value="super-admin"', false);
});

it('assigns multiple roles and department atomically and records an audit', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $admin = roleAssignmentActor('admin', $departmentA);
    $target = roleAssignmentActor('sales', $departmentA);

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openEdit', $target->getKey())
        ->set('form.departmentId', (string) $departmentB->getKey())
        ->set('form.roles', ['sales-manager', 'viewer'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Đã cập nhật người dùng');

    $target->refresh();
    $activity = Activity::query()->where('subject_id', $target->getKey())->sole();

    expect($target->department_id)->toBe($departmentB->getKey())
        ->and($target->getRoleNames()->sort()->values()->all())->toBe(['sales-manager', 'viewer'])
        ->and(app(DataScopeResolver::class)->resolve($target))->toBe(DataScope::Department)
        ->and($activity->causer_id)->toBe($admin->getKey())
        ->and($activity->event)->toBe('updated')
        ->and($activity->properties->get('old')['department_id'])->toBe($departmentA->getKey())
        ->and($activity->properties->get('old')['roles'])->toBe(['sales'])
        ->and($activity->properties->get('new')['department_id'])->toBe($departmentB->getKey())
        ->and($activity->properties->get('new')['roles'])->toBe(['sales-manager', 'viewer']);
});

it('prevents regular admins from assigning or editing super admins', function (): void {
    $admin = roleAssignmentActor();
    $target = roleAssignmentActor('sales');
    $superAdmin = roleAssignmentActor('super-admin');

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openEdit', $target->getKey())
        ->set('form.roles', ['super-admin'])
        ->call('save')
        ->assertForbidden();

    expect($target->refresh()->hasRole('sales'))->toBeTrue()
        ->and($target->hasRole('super-admin'))->toBeFalse()
        ->and(Gate::forUser($admin)->denies('update', $superAdmin))->toBeTrue();

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openEdit', $superAdmin->getKey())
        ->assertForbidden();
});

it('only allows super admins to manage IT department membership', function (): void {
    $it = Department::factory()->create(['code' => 'IT']);
    $sales = Department::factory()->create(['code' => 'SALES']);
    $admin = roleAssignmentActor('admin', $sales);
    $superAdmin = roleAssignmentActor('super-admin', $sales);
    $target = roleAssignmentActor('sales', $sales);

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openEdit', $target->getKey())
        ->set('form.departmentId', (string) $it->getKey())
        ->call('save')
        ->assertForbidden();

    expect($target->refresh()->department_id)->toBe($sales->getKey());

    Livewire::actingAs($superAdmin)
        ->test(UserList::class)
        ->call('openEdit', $target->getKey())
        ->set('form.departmentId', (string) $it->getKey())
        ->call('save')
        ->assertHasNoErrors();

    expect($target->refresh()->department_id)->toBe($it->getKey());
});

it('rejects removing privileges or locking the last active administrator', function (): void {
    $lastAdmin = roleAssignmentActor();

    $component = Livewire::actingAs($lastAdmin)
        ->test(UserList::class)
        ->call('openEdit', $lastAdmin->getKey())
        ->set('form.roles', ['sales'])
        ->call('save')
        ->assertHasErrors('form.roles')
        ->assertSee('quản trị viên đang hoạt động cuối cùng');

    expect($lastAdmin->refresh()->is_active)->toBeTrue()
        ->and($lastAdmin->hasRole('admin'))->toBeTrue()
        ->and(Activity::query()->count())->toBe(0);

    $component
        ->set('form.roles', ['admin'])
        ->set('form.isActive', false)
        ->call('save')
        ->assertHasErrors('form.roles');

    expect($lastAdmin->refresh()->is_active)->toBeTrue()
        ->and($lastAdmin->hasRole('admin'))->toBeTrue()
        ->and(Activity::query()->count())->toBe(0);
});

it('allows demotion when another active administrator remains', function (): void {
    $actor = roleAssignmentActor();
    $target = roleAssignmentActor();

    Livewire::actingAs($actor)
        ->test(UserList::class)
        ->call('openEdit', $target->getKey())
        ->set('form.roles', ['sales'])
        ->set('form.isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($target->refresh()->is_active)->toBeFalse()
        ->and($target->hasRole('sales'))->toBeTrue()
        ->and($target->hasRole('admin'))->toBeFalse()
        ->and(Activity::query()->where('subject_id', $target->getKey())->count())->toBe(1);
});

it('audits account changes without storing passwords or password hashes', function (): void {
    $admin = roleAssignmentActor();
    $target = roleAssignmentActor('sales');
    $oldHash = $target->password;
    $newPassword = 'SecretChanged@123';

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openEdit', $target->getKey())
        ->set('form.name', 'Audited User')
        ->set('form.password', $newPassword)
        ->set('form.passwordConfirmation', $newPassword)
        ->set('form.roles', ['sales-manager'])
        ->call('save')
        ->assertHasNoErrors();

    $activity = Activity::query()->where('subject_id', $target->getKey())->sole();
    $properties = $activity->properties->toJson();

    expect($activity->properties->get('password_changed'))->toBeTrue()
        ->and($properties)->not->toContain($newPassword)
        ->and($properties)->not->toContain($oldHash)
        ->and($activity->properties->get('old'))->not->toHaveKeys(['password', 'password_hash'])
        ->and($activity->properties->get('new'))->not->toHaveKeys(['password', 'password_hash']);
});

it('validates that at least one configured role is selected', function (): void {
    $admin = roleAssignmentActor();

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openCreate')
        ->set('form.name', 'Invalid Role User')
        ->set('form.email', 'invalid-role@salesflow.test')
        ->set('form.password', 'Password@123')
        ->set('form.passwordConfirmation', 'Password@123')
        ->set('form.roles', ['unknown-role'])
        ->call('save')
        ->assertHasErrors('form.roles.0');
});
