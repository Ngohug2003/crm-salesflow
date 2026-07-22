<?php

use App\Livewire\Users\UserList;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function userFormActor(string $role = 'admin', ?Department $department = null): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('shows create and edit actions to authorized users', function (): void {
    $admin = userFormActor();
    $target = User::factory()->create(['name' => 'Người cần sửa']);

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->assertSee('Tạo người dùng')
        ->assertSee('Sửa')
        ->call('openCreate')
        ->assertSet('showForm', true)
        ->assertSee('Tài khoản mới được xác thực email tự động')
        ->call('cancelForm')
        ->call('openEdit', $target->getKey())
        ->assertSet('form.userId', $target->getKey())
        ->assertSet('form.name', 'Người cần sửa')
        ->assertSee('Để trống mật khẩu nếu không muốn thay đổi');
});

it('creates a normalized verified user with a hashed password and assigned role', function (): void {
    $department = Department::factory()->create();
    $admin = userFormActor();

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openCreate')
        ->set('form.name', '  Nguyễn Văn Mới  ')
        ->set('form.email', '  NEW.USER@SALESFLOW.TEST  ')
        ->set('form.departmentId', (string) $department->getKey())
        ->set('form.roles', ['sales'])
        ->set('form.password', 'StrongPass@123')
        ->set('form.passwordConfirmation', 'StrongPass@123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false)
        ->assertSee('Đã tạo người dùng Nguyễn Văn Mới.');

    $user = User::query()->where('email', 'new.user@salesflow.test')->sole();

    expect($user->name)->toBe('Nguyễn Văn Mới')
        ->and($user->department_id)->toBe($department->getKey())
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('StrongPass@123', $user->password))->toBeTrue()
        ->and($user->hasRole('sales'))->toBeTrue();
});

it('validates unique email active department and password confirmation', function (): void {
    $admin = userFormActor();
    User::factory()->create(['email' => 'existing@salesflow.test']);
    $inactiveDepartment = Department::factory()->inactive()->create();

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openCreate')
        ->set('form.name', 'Invalid User')
        ->set('form.email', 'EXISTING@SALESFLOW.TEST')
        ->set('form.departmentId', (string) $inactiveDepartment->getKey())
        ->set('form.roles', ['sales'])
        ->set('form.password', 'short')
        ->set('form.passwordConfirmation', 'different')
        ->call('save')
        ->assertHasErrors([
            'form.email' => 'unique',
            'form.departmentId' => 'exists',
            'form.password',
            'form.passwordConfirmation' => 'same',
        ]);
});

it('updates account fields without replacing an omitted password', function (): void {
    $oldDepartment = Department::factory()->create();
    $newDepartment = Department::factory()->create();
    $admin = userFormActor();
    $target = userFormActor('viewer', $oldDepartment);
    $originalPassword = $target->password;

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openEdit', $target->getKey())
        ->set('form.name', 'Tên Đã Sửa')
        ->set('form.email', 'UPDATED@SALESFLOW.TEST')
        ->set('form.departmentId', (string) $newDepartment->getKey())
        ->set('form.isActive', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Đã cập nhật người dùng Tên Đã Sửa.');

    $target->refresh();

    expect($target->name)->toBe('Tên Đã Sửa')
        ->and($target->email)->toBe('updated@salesflow.test')
        ->and($target->department_id)->toBe($newDepartment->getKey())
        ->and($target->is_active)->toBeFalse()
        ->and($target->password)->toBe($originalPassword)
        ->and($target->hasRole('viewer'))->toBeTrue();
});

it('allows an inactive current department but rejects assigning it to another user', function (): void {
    $inactiveDepartment = Department::factory()->inactive()->create();
    $admin = userFormActor();
    $existingMember = User::factory()->create(['department_id' => $inactiveDepartment->getKey()]);
    $otherUser = User::factory()->create();
    $existingMember->assignRole('sales');
    $otherUser->assignRole('sales');

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openEdit', $existingMember->getKey())
        ->set('form.name', 'Vẫn thuộc phòng ban cũ')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openEdit', $otherUser->getKey())
        ->set('form.departmentId', (string) $inactiveDepartment->getKey())
        ->call('save')
        ->assertHasErrors(['form.departmentId' => 'exists']);
});

it('updates the password and prevents a locked account from logging in', function (): void {
    $admin = userFormActor();
    $target = User::factory()->create([
        'email' => 'locked-user@salesflow.test',
        'password' => 'OldPassword@123',
    ]);
    $target->assignRole('sales');

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('openEdit', $target->getKey())
        ->set('form.password', 'NewPassword@123')
        ->set('form.passwordConfirmation', 'NewPassword@123')
        ->set('form.isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(Hash::check('NewPassword@123', $target->refresh()->password))->toBeTrue();

    $this->post('/logout');
    $this->post('/login', [
        'email' => $target->email,
        'password' => 'NewPassword@123',
    ])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rejects user form actions without write permissions', function (): void {
    $department = Department::factory()->create();
    $manager = userFormActor('sales-manager', $department);
    $target = User::factory()->create(['department_id' => $department->getKey()]);

    Livewire::actingAs($manager)
        ->test(UserList::class)
        ->assertDontSee('Tạo người dùng')
        ->assertDontSee('wire:click="openEdit', false)
        ->call('openCreate')
        ->assertForbidden();

    Livewire::actingAs($manager)
        ->test(UserList::class)
        ->call('openEdit', $target->getKey())
        ->assertForbidden();
});

it('prevents department scoped writers from moving users outside their department', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $manager = userFormActor('sales-manager', $departmentA);
    $manager->givePermissionTo('users.update');
    $target = User::factory()->create(['department_id' => $departmentA->getKey()]);
    $target->assignRole('sales');

    Livewire::actingAs($manager)
        ->test(UserList::class)
        ->call('openEdit', $target->getKey())
        ->set('form.departmentId', (string) $departmentB->getKey())
        ->call('save')
        ->assertForbidden();

    expect($target->refresh()->department_id)->toBe($departmentA->getKey());
});
