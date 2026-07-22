<?php

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates departments with auto-incrementing identifiers and typed state', function (): void {
    $department = Department::factory()->create([
        'is_active' => true,
        'sort_order' => 15,
    ]);

    expect($department->getKey())->toBeInt()->toBeGreaterThan(0)
        ->and($department->is_active)->toBeTrue()
        ->and($department->sort_order)->toBe(15);
});

it('supports parent and ordered child department relationships', function (): void {
    $parent = Department::factory()->create();
    $second = Department::factory()->childOf($parent)->create(['name' => 'Second', 'sort_order' => 20]);
    $first = Department::factory()->childOf($parent)->create(['name' => 'First', 'sort_order' => 10]);

    expect($first->parent->is($parent))->toBeTrue()
        ->and($parent->children)->toHaveCount(2)
        ->and($parent->children->modelKeys())->toBe([$first->getKey(), $second->getKey()]);
});

it('filters active departments', function (): void {
    $active = Department::factory()->create();
    Department::factory()->inactive()->create();

    expect(Department::query()->active()->pluck('id')->all())->toBe([$active->getKey()]);
});

it('links users to their department', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->getKey()]);

    expect($user->department->is($department))->toBeTrue()
        ->and($department->users()->sole()->is($user))->toBeTrue();
});

it('seeds the default department tree idempotently and assigns the demo admin', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $management = Department::query()->where('code', 'MANAGEMENT')->sole();
    $sales = Department::query()->where('code', 'SALES')->sole();
    $it = Department::query()->where('code', 'IT')->sole();
    $admin = User::query()->where('email', 'admin@salesflow.test')->sole();
    $itAdmin = User::query()->where('email', 'it.admin@salesflow.test')->sole();

    expect(Department::query()->count())->toBe(4)
        ->and($sales->parent->is($management))->toBeTrue()
        ->and($it->parent->is($management))->toBeTrue()
        ->and($admin->department->is($management))->toBeTrue()
        ->and($itAdmin->department->is($it))->toBeTrue()
        ->and($itAdmin->hasRole('admin'))->toBeTrue();
});

it('nulls hierarchy and user references if a department is removed technically', function (): void {
    $parent = Department::factory()->create();
    $child = Department::factory()->childOf($parent)->create();
    $user = User::factory()->create(['department_id' => $parent->getKey()]);

    $parent->delete();

    expect($child->refresh()->parent_id)->toBeNull()
        ->and($user->refresh()->department_id)->toBeNull();
});
