<?php

use App\Livewire\Departments\DepartmentManagement;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function departmentManager(): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    Role::findOrCreate('super-admin', 'web');
    $user->assignRole('super-admin');

    return $user;
}

it('protects the department management route', function (): void {
    $this->get('/settings/departments')->assertRedirect('/login');

    $this->actingAs(departmentManager())
        ->get('/settings/departments')
        ->assertOk()
        ->assertSee('Phòng ban')
        ->assertSee('Tạo phòng ban')
        ->assertSee('wire:navigate.hover', false);
});

it('creates a department with normalized data', function (): void {
    $parent = Department::factory()->create();

    Livewire::actingAs(departmentManager())
        ->test(DepartmentManagement::class)
        ->call('openCreate')
        ->assertDispatched('modal-show', name: 'department-form')
        ->set('form.name', '  Kinh doanh Miền Nam  ')
        ->set('form.code', 'sales-south')
        ->set('form.description', '  Phụ trách thị trường miền Nam.  ')
        ->set('form.parentId', (string) $parent->id)
        ->set('form.sortOrder', 25)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('modal-close', name: 'department-form')
        ->assertSee('Đã tạo phòng ban mới.');

    $this->assertDatabaseHas('departments', [
        'name' => 'Kinh doanh Miền Nam',
        'code' => 'SALES-SOUTH',
        'description' => 'Phụ trách thị trường miền Nam.',
        'parent_id' => $parent->id,
        'sort_order' => 25,
        'is_active' => true,
    ]);
});

it('validates department codes and active parents', function (): void {
    Department::factory()->create(['code' => 'EXISTING']);
    $inactiveParent = Department::factory()->inactive()->create();

    Livewire::actingAs(departmentManager())
        ->test(DepartmentManagement::class)
        ->call('openCreate')
        ->set('form.name', 'Phòng ban mới')
        ->set('form.code', 'existing')
        ->set('form.parentId', (string) $inactiveParent->id)
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique', 'form.parentId' => 'exists'])
        ->set('form.code', 'INVALID CODE!')
        ->set('form.parentId', '')
        ->call('save')
        ->assertHasErrors(['form.code' => 'regex']);
});

it('updates a department and prevents hierarchy cycles', function (): void {
    $parent = Department::factory()->create(['name' => 'Parent']);
    $child = Department::factory()->childOf($parent)->create(['name' => 'Child']);

    Livewire::actingAs(departmentManager())
        ->test(DepartmentManagement::class)
        ->call('openEdit', $parent->id)
        ->set('form.parentId', (string) $child->id)
        ->call('save')
        ->assertHasErrors('form.parentId');

    Livewire::actingAs(departmentManager())
        ->test(DepartmentManagement::class)
        ->call('openEdit', $child->id)
        ->set('form.name', 'Child Updated')
        ->set('form.code', 'CHILD-UPDATED')
        ->set('form.parentId', '')
        ->set('form.sortOrder', 12)
        ->call('save')
        ->assertHasNoErrors();

    expect($child->refresh()->name)->toBe('Child Updated')
        ->and($child->code)->toBe('CHILD-UPDATED')
        ->and($child->parent_id)->toBeNull()
        ->and($child->sort_order)->toBe(12);
});

it('keeps active state consistent across the department hierarchy', function (): void {
    $parent = Department::factory()->create();
    $child = Department::factory()->childOf($parent)->create();

    $component = Livewire::actingAs(departmentManager())
        ->test(DepartmentManagement::class)
        ->call('toggleActive', $parent->id)
        ->assertSet('noticeType', 'error');

    expect($parent->refresh()->is_active)->toBeTrue();

    $component->call('toggleActive', $child->id);
    expect($child->refresh()->is_active)->toBeFalse();

    $component->call('toggleActive', $parent->id);
    expect($parent->refresh()->is_active)->toBeFalse();

    $component->call('toggleActive', $child->id)->assertSet('noticeType', 'error');
    expect($child->refresh()->is_active)->toBeFalse();

    $component->call('toggleActive', $parent->id)
        ->call('toggleActive', $child->id)
        ->assertSet('noticeType', 'success');

    expect($parent->refresh()->is_active)->toBeTrue()
        ->and($child->refresh()->is_active)->toBeTrue();
});

it('applies hierarchy rules when status is changed through the edit form', function (): void {
    $parent = Department::factory()->create();
    Department::factory()->childOf($parent)->create();

    Livewire::actingAs(departmentManager())
        ->test(DepartmentManagement::class)
        ->call('openEdit', $parent->id)
        ->set('form.isActive', false)
        ->call('save')
        ->assertHasErrors('form.isActive');

    expect($parent->refresh()->is_active)->toBeTrue();
});

it('searches and filters departments by status', function (): void {
    Department::factory()->create(['name' => 'Kinh doanh Hà Nội', 'code' => 'SALES-HN']);
    Department::factory()->inactive()->create(['name' => 'Kinh doanh Huế', 'code' => 'SALES-HUE']);

    Livewire::actingAs(departmentManager())
        ->test(DepartmentManagement::class)
        ->set('search', 'hue')
        ->assertSee('SALES-HUE')
        ->assertDontSee('SALES-HN')
        ->set('search', '')
        ->set('status', 'active')
        ->assertSee('SALES-HN')
        ->assertDontSee('SALES-HUE')
        ->set('status', 'inactive')
        ->assertSee('SALES-HUE')
        ->assertDontSee('SALES-HN');
});

it('deletes a department after confirmation', function (): void {
    $department = Department::factory()->create(['name' => 'Phòng ban tạm']);

    Livewire::actingAs(departmentManager())
        ->test(DepartmentManagement::class)
        ->call('openDelete', $department->id)
        ->assertSet('pendingDeleteId', $department->id)
        ->assertSet('pendingDeleteName', 'Phòng ban tạm')
        ->assertDispatched('modal-show')
        ->call('confirmDelete')
        ->assertSet('pendingDeleteId', null)
        ->assertSet('noticeType', 'success')
        ->assertSee('Đã xóa phòng ban Phòng ban tạm.')
        ->assertDispatched('modal-close');

    $this->assertDatabaseMissing('departments', ['id' => $department->id]);
});

it('does not delete departments that still have children or users', function (): void {
    $parent = Department::factory()->create();
    Department::factory()->childOf($parent)->create();
    $assignedDepartment = Department::factory()->create();
    User::factory()->create(['department_id' => $assignedDepartment->id]);

    Livewire::actingAs(departmentManager())
        ->test(DepartmentManagement::class)
        ->call('openDelete', $parent->id)
        ->call('confirmDelete')
        ->assertSet('pendingDeleteId', $parent->id)
        ->assertSet('deleteError', 'Không thể xóa phòng ban đang có phòng ban con. Hãy chuyển hoặc xóa các phòng ban con trước.')
        ->call('cancelDelete')
        ->call('openDelete', $assignedDepartment->id)
        ->call('confirmDelete')
        ->assertSet('pendingDeleteId', $assignedDepartment->id)
        ->assertSet('deleteError', 'Không thể xóa phòng ban đang có người dùng. Hãy chuyển người dùng sang phòng ban khác trước.');

    $this->assertDatabaseHas('departments', ['id' => $parent->id]);
    $this->assertDatabaseHas('departments', ['id' => $assignedDepartment->id]);
});
