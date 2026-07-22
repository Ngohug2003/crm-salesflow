<?php

declare(strict_types=1);

use App\Data\LeadFilterData;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Tag;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\EloquentLeadRepository;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function p303Actor(string $role, ?Department $department = null): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('binds the lead repository contract to the eloquent implementation', function (): void {
    expect(app(LeadRepository::class))->toBeInstanceOf(EloquentLeadRepository::class);
});

it('applies all department owned and read-only data scopes before filtering', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $admin = p303Actor('admin', $departmentB);
    $manager = p303Actor('sales-manager', $departmentA);
    $sales = p303Actor('sales', $departmentA);
    $colleague = User::factory()->create(['department_id' => $departmentA->getKey()]);
    $outsider = User::factory()->create(['department_id' => $departmentB->getKey()]);
    $viewer = p303Actor('viewer', $departmentB);

    $owned = Lead::factory()->ownedBy($sales)->create();
    $sameDepartment = Lead::factory()->ownedBy($colleague)->create();
    $otherDepartment = Lead::factory()->ownedBy($outsider)->create();
    $unassigned = Lead::factory()->create(['department_id' => null, 'owner_id' => null]);
    $targetIds = collect([$owned, $sameDepartment, $otherDepartment, $unassigned])
        ->pluck('id')
        ->sort()
        ->values()
        ->all();
    $repository = app(LeadRepository::class);

    $visibleIds = static fn (User $actor): array => $repository
        ->visibleTo($actor)
        ->whereIn('id', $targetIds)
        ->orderBy('id')
        ->pluck('id')
        ->all();

    expect($visibleIds($admin))->toBe($targetIds)
        ->and($visibleIds($manager))->toBe(collect([$owned, $sameDepartment])->pluck('id')->sort()->values()->all())
        ->and($visibleIds($sales))->toBe([$owned->getKey()])
        ->and($visibleIds($viewer))->toBe($targetIds);
});

it('searches contact and company fields case insensitively and excludes deleted leads', function (): void {
    $actor = p303Actor('admin');
    $name = Lead::factory()->create(['full_name' => 'Nguyễn MINH Anh']);
    $email = Lead::factory()->create(['email' => 'CONTACT@EXAMPLE.TEST']);
    $phone = Lead::factory()->create(['phone' => '0901234567']);
    $secondaryPhone = Lead::factory()->create(['secondary_phone' => '0287654321']);
    $company = Lead::factory()->create(['company_name' => 'Công ty Sao Việt']);
    $deleted = Lead::factory()->create(['full_name' => 'Nguyễn Minh Đã Xóa']);
    $deleted->delete();
    $repository = app(LeadRepository::class);

    $idsFor = static fn (string $search): array => $repository
        ->filteredVisibleTo($actor, new LeadFilterData(search: $search))
        ->pluck('id')
        ->all();

    expect($idsFor('minh anh'))->toBe([$name->getKey()])
        ->and($idsFor('contact@example'))->toBe([$email->getKey()])
        ->and($idsFor('090123'))->toBe([$phone->getKey()])
        ->and($idsFor('028765'))->toBe([$secondaryPhone->getKey()])
        ->and($idsFor('sao việt'))->toBe([$company->getKey()])
        ->and($idsFor('đã xóa'))->toBe([]);
});

it('combines taxonomy assignment lifecycle and date filters without duplicate leads', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create(['department_id' => $department->getKey()]);
    $actor = p303Actor('admin');
    $source = LeadSource::factory()->create();
    $tag = Tag::factory()->create();
    $extraTag = Tag::factory()->create();
    $matching = Lead::factory()->ownedBy($owner)->create([
        'lead_source_id' => $source->getKey(),
        'status' => LeadStatus::Qualified,
        'priority' => LeadPriority::High,
        'created_at' => '2026-07-15 10:00:00',
    ]);
    $matching->tags()->attach([$tag->getKey(), $extraTag->getKey()]);

    Lead::factory()->ownedBy($owner)->create([
        'lead_source_id' => $source->getKey(),
        'status' => LeadStatus::New,
        'priority' => LeadPriority::High,
        'created_at' => '2026-07-15 10:00:00',
    ])->tags()->attach($tag);
    Lead::factory()->ownedBy($owner)->create([
        'lead_source_id' => $source->getKey(),
        'status' => LeadStatus::Qualified,
        'priority' => LeadPriority::High,
        'created_at' => '2026-06-30 23:59:59',
    ])->tags()->attach($tag);

    $filters = new LeadFilterData(
        status: LeadStatus::Qualified,
        priority: LeadPriority::High,
        sourceId: $source->getKey(),
        tagId: $tag->getKey(),
        ownerId: $owner->getKey(),
        departmentId: $department->getKey(),
        createdFrom: CarbonImmutable::parse('2026-07-01'),
        createdTo: CarbonImmutable::parse('2026-07-31'),
    );

    expect(app(LeadRepository::class)->filteredVisibleTo($actor, $filters)->pluck('id')->all())
        ->toBe([$matching->getKey()]);
});

it('uses an allowlist for sorting and a stable identifier fallback', function (): void {
    $actor = p303Actor('admin');
    $first = Lead::factory()->create([
        'estimated_value' => '100.00',
        'created_at' => '2026-07-01 10:00:00',
    ]);
    $second = Lead::factory()->create([
        'estimated_value' => '100.00',
        'created_at' => '2026-07-02 10:00:00',
    ]);
    $third = Lead::factory()->create([
        'estimated_value' => '200.00',
        'created_at' => '2026-07-03 10:00:00',
    ]);
    $repository = app(LeadRepository::class);

    $allowed = $repository->filteredVisibleTo($actor, new LeadFilterData(
        sortBy: 'estimated_value',
        sortDirection: 'asc',
    ))->pluck('id')->all();
    $rejected = $repository->filteredVisibleTo($actor, new LeadFilterData(
        sortBy: 'created_at; DROP TABLE leads',
        sortDirection: 'sideways',
    ))->pluck('id')->all();

    expect($allowed)->toBe([$first->getKey(), $second->getKey(), $third->getKey()])
        ->and($rejected)->toBe([$third->getKey(), $second->getKey(), $first->getKey()])
        ->and(Schema::hasTable('leads'))->toBeTrue();
});

it('clamps pagination and eager loads list relations', function (): void {
    $department = Department::factory()->create();
    $actor = p303Actor('admin');
    $owner = User::factory()->create(['department_id' => $department->getKey()]);
    $lead = Lead::factory()->ownedBy($owner)->create();
    $lead->tags()->attach(Tag::factory()->create());
    $repository = app(LeadRepository::class);

    $maximum = $repository->paginateVisibleTo($actor, new LeadFilterData, 500);
    $minimum = $repository->paginateVisibleTo($actor, new LeadFilterData, 0);
    $result = $maximum->getCollection()->firstOrFail();

    expect($maximum->perPage())->toBe(100)
        ->and($minimum->perPage())->toBe(1)
        ->and($result->relationLoaded('source'))->toBeTrue()
        ->and($result->relationLoaded('owner'))->toBeTrue()
        ->and($result->relationLoaded('department'))->toBeTrue()
        ->and($result->relationLoaded('tags'))->toBeTrue();
});

it('finds only leads visible to the actor and eager loads their relations', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $manager = p303Actor('sales-manager', $departmentA);
    $ownerA = User::factory()->create(['department_id' => $departmentA->getKey()]);
    $ownerB = User::factory()->create(['department_id' => $departmentB->getKey()]);
    $visible = Lead::factory()->ownedBy($ownerA)->create();
    $hidden = Lead::factory()->ownedBy($ownerB)->create();
    $repository = app(LeadRepository::class);

    $result = $repository->findVisibleOrFail($manager, $visible->getKey());

    expect($result->is($visible))->toBeTrue()
        ->and($result->relationLoaded('source'))->toBeTrue()
        ->and($result->relationLoaded('owner'))->toBeTrue()
        ->and($result->relationLoaded('department'))->toBeTrue()
        ->and($result->relationLoaded('tags'))->toBeTrue()
        ->and(fn () => $repository->findVisibleOrFail($manager, $hidden->getKey()))
        ->toThrow(ModelNotFoundException::class);
});
