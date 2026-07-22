<?php

declare(strict_types=1);

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Livewire\Leads\LeadList;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Tag;
use App\Models\User;
use App\Services\LeadDirectoryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function p305User(string $role, ?Department $department = null, array $attributes = []): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'is_active' => true,
        ...$attributes,
    ]);
    $user->assignRole($role);

    return $user;
}

it('protects the lead route and displays navigation for all five roles', function (): void {
    $this->get('/leads')->assertRedirect('/login');

    foreach (['super-admin', 'admin', 'sales-manager', 'sales', 'viewer'] as $role) {
        $actor = p305User($role);

        $this->actingAs($actor)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee('Danh sách Lead')
            ->assertSee('Tìm kiếm');

        $this->actingAs($actor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('leads.index').'"', false);
    }

    $unauthorized = User::factory()->create();

    $this->actingAs($unauthorized)->get(route('leads.index'))->assertForbidden();
    $this->actingAs($unauthorized)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('href="'.route('leads.index').'"', false);
    Livewire::actingAs($unauthorized)->test(LeadList::class)->assertForbidden();
});

it('hydrates complete filter and sort state from the url', function (): void {
    $department = Department::factory()->create();
    $admin = p305User('admin');
    $owner = User::factory()->create(['department_id' => $department->getKey()]);
    $source = LeadSource::factory()->create();
    $tag = Tag::factory()->create();
    $matching = Lead::factory()->ownedBy($owner)->create([
        'lead_source_id' => $source->getKey(),
        'full_name' => 'URL Target Lead',
        'email' => 'url-lead@salesflow.test',
        'status' => LeadStatus::Qualified,
        'priority' => LeadPriority::High,
        'created_at' => '2026-07-15 10:00:00',
    ]);
    $matching->tags()->attach($tag);
    Lead::factory()->create(['full_name' => 'Other Lead']);

    Livewire::withQueryParams([
        'q' => 'url target',
        'status' => LeadStatus::Qualified->value,
        'priority' => LeadPriority::High->value,
        'source' => (string) $source->getKey(),
        'tag' => (string) $tag->getKey(),
        'owner' => (string) $owner->getKey(),
        'department' => (string) $department->getKey(),
        'from' => '2026-07-01',
        'to' => '2026-07-31',
        'sort' => 'full_name',
        'direction' => 'asc',
        'per_page' => 25,
    ])->actingAs($admin)
        ->test(LeadList::class)
        ->assertSet('search', 'url target')
        ->assertSet('status', LeadStatus::Qualified->value)
        ->assertSet('priority', LeadPriority::High->value)
        ->assertSet('source', (string) $source->getKey())
        ->assertSet('tag', (string) $tag->getKey())
        ->assertSet('owner', (string) $owner->getKey())
        ->assertSet('department', (string) $department->getKey())
        ->assertSet('dateFrom', '2026-07-01')
        ->assertSet('dateTo', '2026-07-31')
        ->assertSet('sort', 'full_name')
        ->assertSet('direction', 'asc')
        ->assertSet('perPage', 25)
        ->assertSee($matching->email)
        ->assertDontSee('Other Lead');
});

it('normalizes invalid url state before building repository filters', function (): void {
    $admin = p305User('admin');

    Livewire::withQueryParams([
        'status' => 'not-a-status',
        'priority' => 'root',
        'source' => '1 OR 1=1',
        'tag' => '-5',
        'owner' => '0',
        'department' => 'abc',
        'from' => '2026-02-30',
        'to' => 'invalid',
        'sort' => 'created_at; DROP TABLE leads',
        'direction' => 'sideways',
        'per_page' => 999,
    ])->actingAs($admin)
        ->test(LeadList::class)
        ->assertSet('status', 'all')
        ->assertSet('priority', 'all')
        ->assertSet('source', 'all')
        ->assertSet('tag', 'all')
        ->assertSet('owner', 'all')
        ->assertSet('department', 'all')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '')
        ->assertSet('sort', 'created_at')
        ->assertSet('direction', 'desc')
        ->assertSet('perPage', 15);
});

it('limits list records and filter options to the actor data scope', function (): void {
    $departmentA = Department::factory()->create(['name' => 'Kinh doanh A', 'code' => 'SALES-A']);
    $departmentB = Department::factory()->create(['name' => 'Kinh doanh B', 'code' => 'SALES-B']);
    $manager = p305User('sales-manager', $departmentA, ['name' => 'Manager A']);
    $sales = p305User('sales', $departmentA, ['name' => 'Sales A']);
    $colleague = User::factory()->create(['department_id' => $departmentA->getKey(), 'name' => 'Colleague A']);
    $outsider = User::factory()->create(['department_id' => $departmentB->getKey(), 'name' => 'Outsider B']);
    $owned = Lead::factory()->ownedBy($sales)->create(['full_name' => 'Lead Owned']);
    Lead::factory()->ownedBy($colleague)->create(['full_name' => 'Lead Colleague']);
    Lead::factory()->ownedBy($outsider)->create(['full_name' => 'Lead Outsider']);
    $service = app(LeadDirectoryService::class);

    Livewire::actingAs($manager)
        ->test(LeadList::class)
        ->assertSee('Lead Owned')
        ->assertSee('Lead Colleague')
        ->assertDontSee('Lead Outsider')
        ->assertSee('SALES-A')
        ->assertDontSee('SALES-B');

    Livewire::actingAs($sales)
        ->test(LeadList::class)
        ->assertSee($owned->full_name)
        ->assertDontSee('Lead Colleague')
        ->assertDontSee('Lead Outsider');

    expect($service->ownerOptions($manager)->pluck('id')->all())
        ->toContain($manager->getKey(), $sales->getKey(), $colleague->getKey())
        ->not->toContain($outsider->getKey())
        ->and($service->departmentOptions($manager)->pluck('id')->all())->toBe([$departmentA->getKey()])
        ->and($service->ownerOptions($sales)->pluck('id')->all())->toBe([$sales->getKey()]);
});

it('selects only the current page and clears selection when the page changes', function (): void {
    $admin = p305User('admin');
    $leads = collect();

    foreach (range(1, 16) as $number) {
        $leads->push(Lead::factory()->create([
            'full_name' => sprintf('Lead %02d', $number),
            'created_at' => sprintf('2026-07-%02d 10:00:00', $number),
        ]));
    }

    $latest = $leads->last();
    $oldest = $leads->first();

    Livewire::actingAs($admin)
        ->test(LeadList::class)
        ->set('perPage', 10)
        ->assertSee($latest->full_name)
        ->assertDontSee($oldest->full_name)
        ->call('togglePageSelection')
        ->assertCount('selectedLeadIds', 10)
        ->call('setPage', 2)
        ->assertSet('selectedLeadIds', [])
        ->assertSee($oldest->full_name)
        ->assertDontSee($latest->full_name)
        ->set('selectedLeadIds', [$latest->getKey(), $oldest->getKey()])
        ->assertSet('selectedLeadIds', [$oldest->getKey()])
        ->call('togglePageSelection')
        ->assertCount('selectedLeadIds', 6);
});

it('shows distinct empty states and responsive list markup', function (): void {
    $admin = p305User('admin');

    Livewire::actingAs($admin)
        ->test(LeadList::class)
        ->assertSee('Chưa có Lead trong phạm vi của bạn');

    Lead::factory()->create(['full_name' => 'Visible Lead']);

    Livewire::actingAs($admin)
        ->test(LeadList::class)
        ->assertSee('Visible Lead')
        ->assertSee('hidden overflow-x-auto lg:block', false)
        ->assertSee('grid gap-3 lg:hidden', false)
        ->set('search', 'khong-co-ket-qua')
        ->assertSee('Không có Lead phù hợp bộ lọc')
        ->call('clearFilters')
        ->assertSee('Visible Lead')
        ->assertSet('search', '');
});
