<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function p304User(string $role, ?Department $department = null): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('allows super admins and admins to manage leads across all scopes', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create(['department_id' => $department->getKey()]);
    $lead = Lead::factory()->ownedBy($owner)->create();
    $unassigned = Lead::factory()->create(['owner_id' => null, 'department_id' => null]);
    $superAdmin = p304User('super-admin');
    $admin = p304User('admin');

    foreach ([$superAdmin, $admin] as $actor) {
        expect(Gate::forUser($actor)->allows('viewAny', Lead::class))->toBeTrue()
            ->and(Gate::forUser($actor)->allows('create', Lead::class))->toBeTrue();

        foreach ([$lead, $unassigned] as $target) {
            expect(Gate::forUser($actor)->allows('view', $target))->toBeTrue()
                ->and(Gate::forUser($actor)->allows('update', $target))->toBeTrue()
                ->and(Gate::forUser($actor)->allows('delete', $target))->toBeTrue()
                ->and(Gate::forUser($actor)->allows('restore', $target))->toBeTrue()
                ->and(Gate::forUser($actor)->allows('assign', $target))->toBeTrue()
                ->and(Gate::forUser($actor)->allows('convert', $target))->toBeTrue();
        }
    }

    expect(Gate::forUser($superAdmin)->allows('forceDelete', $lead))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('forceDelete', $lead))->toBeFalse();
});

it('limits sales managers to leads in their department despite all-style permissions', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $manager = p304User('sales-manager', $departmentA);
    $ownerA = User::factory()->create(['department_id' => $departmentA->getKey()]);
    $ownerB = User::factory()->create(['department_id' => $departmentB->getKey()]);
    $sameDepartment = Lead::factory()->ownedBy($ownerA)->create();
    $otherDepartment = Lead::factory()->ownedBy($ownerB)->create();
    $unassigned = Lead::factory()->create(['owner_id' => null, 'department_id' => null]);

    expect($manager->can('leads.view-all'))->toBeTrue()
        ->and($manager->can('leads.update-all'))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('viewAny', Lead::class))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('create', Lead::class))->toBeTrue();

    foreach (['view', 'update', 'delete', 'restore', 'assign', 'convert'] as $ability) {
        expect(Gate::forUser($manager)->allows($ability, $sameDepartment))->toBeTrue()
            ->and(Gate::forUser($manager)->allows($ability, $otherDepartment))->toBeFalse()
            ->and(Gate::forUser($manager)->allows($ability, $unassigned))->toBeFalse();
    }

    expect(Gate::forUser($manager)->allows('forceDelete', $sameDepartment))->toBeFalse();
});

it('allows sales users to manage only owned leads without assignment permission', function (): void {
    $department = Department::factory()->create();
    $sales = p304User('sales', $department);
    $colleague = User::factory()->create(['department_id' => $department->getKey()]);
    $owned = Lead::factory()->ownedBy($sales)->create();
    $other = Lead::factory()->ownedBy($colleague)->create();

    expect(Gate::forUser($sales)->allows('viewAny', Lead::class))->toBeTrue()
        ->and(Gate::forUser($sales)->allows('create', Lead::class))->toBeTrue()
        ->and(Gate::forUser($sales)->allows('view', $owned))->toBeTrue()
        ->and(Gate::forUser($sales)->allows('update', $owned))->toBeTrue()
        ->and(Gate::forUser($sales)->allows('delete', $owned))->toBeTrue()
        ->and(Gate::forUser($sales)->allows('restore', $owned))->toBeTrue()
        ->and(Gate::forUser($sales)->allows('convert', $owned))->toBeTrue()
        ->and(Gate::forUser($sales)->allows('assign', $owned))->toBeFalse();

    foreach (['view', 'update', 'delete', 'restore', 'assign', 'convert'] as $ability) {
        expect(Gate::forUser($sales)->allows($ability, $other))->toBeFalse();
    }
});

it('keeps viewers read-only even when write permissions are assigned accidentally', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $viewer = p304User('viewer', $departmentA);
    $owner = User::factory()->create(['department_id' => $departmentB->getKey()]);
    $lead = Lead::factory()->ownedBy($owner)->create();
    $viewer->givePermissionTo([
        'leads.create',
        'leads.update',
        'leads.delete',
        'leads.assign',
        'leads.convert',
    ]);

    expect(Gate::forUser($viewer)->allows('viewAny', Lead::class))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('view', $lead))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('create', Lead::class))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('update', $lead))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('delete', $lead))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('restore', $lead))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('assign', $lead))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('convert', $lead))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('forceDelete', $lead))->toBeFalse();
});

it('authorizes restore against the original scope of a soft-deleted lead', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $managerA = p304User('sales-manager', $departmentA);
    $managerB = p304User('sales-manager', $departmentB);
    $sales = p304User('sales', $departmentA);
    $lead = Lead::factory()->ownedBy($sales)->create();
    $lead->delete();
    $trashedLead = Lead::query()->withTrashed()->findOrFail($lead->getKey());

    expect($trashedLead->trashed())->toBeTrue()
        ->and(Gate::forUser($managerA)->allows('restore', $trashedLead))->toBeTrue()
        ->and(Gate::forUser($managerB)->allows('restore', $trashedLead))->toBeFalse()
        ->and(Gate::forUser($sales)->allows('restore', $trashedLead))->toBeTrue();
});

it('denies users without a configured role or lead permissions', function (): void {
    $user = User::factory()->create();
    $lead = Lead::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Lead::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', $lead))->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', Lead::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $lead))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', $lead))->toBeFalse()
        ->and(Gate::forUser($user)->allows('restore', $lead))->toBeFalse()
        ->and(Gate::forUser($user)->allows('assign', $lead))->toBeFalse()
        ->and(Gate::forUser($user)->allows('convert', $lead))->toBeFalse()
        ->and(Gate::forUser($user)->allows('forceDelete', $lead))->toBeFalse();
});
