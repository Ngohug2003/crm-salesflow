<?php

use App\Enums\LeadStatus;
use App\Livewire\Leads\LeadEditor;
use App\Livewire\Leads\LeadLifecycle;
use App\Livewire\Leads\LeadTrash;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadAssignmentHistory;
use App\Models\LeadStatusHistory;
use App\Models\Tag;
use App\Models\User;
use App\Services\DuplicateLeadService;
use App\Services\LeadLifecycleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function p308Actor(string $role, ?Department $department = null, array $attributes = []): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'email_verified_at' => now(),
        'is_active' => true,
        ...$attributes,
    ]);
    $user->assignRole($role);

    return $user;
}

it('normalizes email and Vietnamese phone formats at the model boundary', function (): void {
    $lead = Lead::factory()->create([
        'email' => '  DUPLICATE@EXAMPLE.COM ',
        'phone' => '+84 901-234-567',
        'secondary_phone' => '0084.912.345.678',
    ]);

    expect($lead->getAttribute('email_normalized'))->toBe('duplicate@example.com')
        ->and($lead->getAttribute('phone_normalized'))->toBe('0901234567')
        ->and($lead->getAttribute('secondary_phone_normalized'))->toBe('0912345678')
        ->and(Schema::hasColumns('leads', [
            'email_normalized', 'phone_normalized', 'secondary_phone_normalized',
        ]))->toBeTrue();
});

it('finds visible active and trashed duplicates without leaking another data scope', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $manager = p308Actor('sales-manager', $departmentA);
    $ownerA = p308Actor('sales', $departmentA);
    $ownerB = p308Actor('sales', $departmentB);
    $emailMatch = Lead::factory()->ownedBy($ownerA)->create(['email' => 'same@example.com']);
    $phoneMatch = Lead::factory()->ownedBy($ownerA)->create(['secondary_phone' => '+84 912 345 678']);
    $phoneMatch->delete();
    Lead::factory()->ownedBy($ownerB)->create([
        'email' => 'same@example.com',
        'phone' => '0912345678',
    ]);

    $matches = app(DuplicateLeadService::class)->candidates($manager, [
        'email' => ' SAME@EXAMPLE.COM ',
        'phone' => '0912-345-678',
        'secondary_phone' => null,
    ]);

    expect(collect($matches)->pluck('id')->all())
        ->toContain($emailMatch->getKey(), $phoneMatch->getKey())
        ->toHaveCount(2)
        ->and(collect($matches)->firstWhere('id', $emailMatch->getKey())['matched_fields'])->toContain('Email')
        ->and(collect($matches)->firstWhere('id', $phoneMatch->getKey())['matched_fields'])->toContain('Số điện thoại')
        ->and(collect($matches)->firstWhere('id', $phoneMatch->getKey())['trashed'])->toBeTrue();

    expect(app(DuplicateLeadService::class)->candidates($manager, [
        'email' => 'same@example.com',
    ], $emailMatch->getKey()))->toHaveCount(0);
});

it('warns before saving a duplicate and only persists after explicit confirmation', function (): void {
    $admin = p308Actor('admin');
    $existing = Lead::factory()->create([
        'full_name' => 'Lead đã tồn tại',
        'email' => 'existing@example.com',
    ]);

    $component = Livewire::actingAs($admin)
        ->test(LeadEditor::class)
        ->set('form.fullName', 'Lead mới bị trùng')
        ->set('form.email', ' EXISTING@EXAMPLE.COM ')
        ->call('save')
        ->assertSet('duplicateCandidates.0.id', $existing->getKey())
        ->assertSet('showDuplicateWarning', true)
        ->assertSee('Phát hiện Lead có thể bị trùng')
        ->assertSee('Mở Lead hiện có');

    expect(Lead::query()->where('full_name', 'Lead mới bị trùng')->exists())->toBeFalse();

    $component
        ->call('confirmDuplicateSave')
        ->assertSet('showDuplicateWarning', false)
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Lead::query()->where('full_name', 'Lead mới bị trùng')->sole()->email)
        ->toBe('existing@example.com');
});

it('invalidates duplicate confirmation when contact data changes', function (): void {
    $admin = p308Actor('admin');
    Lead::factory()->create(['email' => 'first@example.com']);
    $second = Lead::factory()->create(['phone' => '0909999999']);

    Livewire::actingAs($admin)
        ->test(LeadEditor::class)
        ->set('form.fullName', 'Lead đổi dữ liệu sau cảnh báo')
        ->set('form.email', 'first@example.com')
        ->call('save')
        ->set('form.email', 'unique@example.com')
        ->set('form.phone', '0909 999 999')
        ->call('confirmDuplicateSave')
        ->assertSet('duplicateCandidates.0.id', $second->getKey());

    expect(Lead::query()->where('full_name', 'Lead đổi dữ liệu sau cảnh báo')->exists())->toBeFalse();
});

it('soft deletes a lead while retaining tags and workflow histories with audit', function (): void {
    $department = Department::factory()->create();
    $admin = p308Actor('admin');
    $owner = p308Actor('sales', $department);
    $lead = Lead::factory()->ownedBy($owner)->create();
    $tag = Tag::factory()->create();
    $lead->tags()->attach($tag);
    LeadAssignmentHistory::query()->create([
        'lead_id' => $lead->getKey(),
        'new_owner_id' => $owner->getKey(),
        'new_department_id' => $department->getKey(),
        'changed_by' => $admin->getKey(),
    ]);
    LeadStatusHistory::query()->create([
        'lead_id' => $lead->getKey(),
        'from_status' => null,
        'to_status' => LeadStatus::New,
        'changed_by' => $admin->getKey(),
    ]);

    Livewire::actingAs($admin)
        ->test(LeadLifecycle::class, ['leadId' => $lead->getKey()])
        ->call('openDelete')
        ->set('deleteReason', 'Không còn nhu cầu')
        ->call('confirmDelete')
        ->assertHasNoErrors()
        ->assertRedirect(route('leads.index'));

    expect(Lead::query()->find($lead->getKey()))->toBeNull()
        ->and(Lead::withTrashed()->findOrFail($lead->getKey())->trashed())->toBeTrue()
        ->and($lead->tags()->count())->toBe(1)
        ->and(LeadAssignmentHistory::query()->where('lead_id', $lead->getKey())->count())->toBe(1)
        ->and(LeadStatusHistory::query()->where('lead_id', $lead->getKey())->count())->toBe(1);

    $activity = Activity::query()->where('subject_type', Lead::class)->where('subject_id', $lead->getKey())->sole();

    expect($activity->event)->toBe('deleted')
        ->and($activity->properties->get('reason'))->toBe('Không còn nhu cầu')
        ->and($activity->properties->get('new')['deleted_at'])->not->toBeNull();
});

it('restores a lead with history and unassigns an inactive owner', function (): void {
    $department = Department::factory()->create();
    $admin = p308Actor('admin');
    $owner = p308Actor('sales', $department, ['is_active' => false]);
    $lead = Lead::factory()->ownedBy($owner)->create();
    $tag = Tag::factory()->create();
    $lead->tags()->attach($tag);
    LeadStatusHistory::query()->create([
        'lead_id' => $lead->getKey(),
        'to_status' => LeadStatus::New,
        'changed_by' => $admin->getKey(),
    ]);
    $lead->delete();

    Livewire::actingAs($admin)
        ->test(LeadTrash::class)
        ->assertSee($lead->full_name)
        ->call('openRestore', $lead->getKey())
        ->set('restoreReason', 'Khôi phục để tiếp tục chăm sóc')
        ->call('confirmRestore')
        ->assertHasNoErrors()
        ->assertSee("Đã khôi phục Lead {$lead->full_name}.");

    $restored = $lead->fresh();

    expect($restored)->not->toBeNull()
        ->and($restored->owner_id)->toBeNull()
        ->and($restored->department_id)->toBe($department->getKey())
        ->and($restored->tags()->count())->toBe(1)
        ->and(LeadStatusHistory::query()->where('lead_id', $lead->getKey())->count())->toBe(1)
        ->and(LeadAssignmentHistory::query()->where('lead_id', $lead->getKey())->sole()->previous_owner_id)->toBe($owner->getKey())
        ->and(LeadAssignmentHistory::query()->where('lead_id', $lead->getKey())->sole()->new_owner_id)->toBeNull();

    $activity = Activity::query()->where('subject_type', Lead::class)->where('subject_id', $lead->getKey())->sole();

    expect($activity->event)->toBe('restored')
        ->and($activity->properties->get('reason'))->toBe('Khôi phục để tiếp tục chăm sóc')
        ->and($activity->properties->get('old')['deleted_at'])->not->toBeNull()
        ->and($activity->properties->get('new')['deleted_at'])->toBeNull();
});

it('protects trash and lifecycle operations with policy and data scope', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $manager = p308Actor('sales-manager', $departmentA);
    $salesA = p308Actor('sales', $departmentA);
    $salesB = p308Actor('sales', $departmentB);
    $viewer = p308Actor('viewer');
    $leadA = Lead::factory()->ownedBy($salesA)->create(['full_name' => 'Lead A']);
    $leadB = Lead::factory()->ownedBy($salesB)->create(['full_name' => 'Lead B']);
    $leadA->delete();
    $leadB->delete();

    $this->actingAs($manager)
        ->get(route('leads.trash'))
        ->assertOk()
        ->assertSee('Lead A')
        ->assertDontSee('Lead B');
    $this->actingAs($viewer)->get(route('leads.trash'))->assertForbidden();

    expect(fn () => app(LeadLifecycleService::class)->restore($manager, $leadB->getKey(), null))
        ->toThrow(ModelNotFoundException::class)
        ->and(fn () => app(LeadLifecycleService::class)->restore($viewer, $leadA->getKey(), null))
        ->toThrow(AuthorizationException::class);

    expect(Lead::onlyTrashed()->count())->toBe(2);
});

it('searches and paginates only trashed leads in the actor scope', function (): void {
    $department = Department::factory()->create();
    $sales = p308Actor('sales', $department);
    $other = p308Actor('sales', $department);

    foreach (range(1, 16) as $number) {
        Lead::factory()->ownedBy($sales)->create([
            'full_name' => sprintf('Lead thùng rác %02d', $number),
        ])->delete();
    }

    Lead::factory()->ownedBy($other)->create(['full_name' => 'Lead ngoài phạm vi'])->delete();

    Livewire::actingAs($sales)
        ->test(LeadTrash::class)
        ->assertSee('Lead thùng rác 16')
        ->assertDontSee('Lead thùng rác 01')
        ->call('nextPage')
        ->assertSee('Lead thùng rác 01')
        ->assertDontSee('Lead thùng rác 16')
        ->set('search', 'không tồn tại')
        ->assertSee('Thùng rác đang trống')
        ->assertDontSee('Lead ngoài phạm vi');
});
