<?php

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Livewire\Leads\LeadEditor;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function p306Actor(string $role, ?Department $department = null, array $attributes = []): User
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

it('protects create detail and edit routes with policy and data scope', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $manager = p306Actor('sales-manager', $departmentA);
    $viewer = p306Actor('viewer', $departmentA);
    $owned = Lead::factory()->create(['department_id' => $departmentA->getKey()]);
    $outside = Lead::factory()->create(['department_id' => $departmentB->getKey()]);

    $this->get(route('leads.create'))->assertRedirect(route('login'));
    $this->get(route('leads.show', $owned))->assertRedirect(route('login'));
    $this->get(route('leads.edit', $owned))->assertRedirect(route('login'));

    $this->actingAs($manager)
        ->get(route('leads.create'))
        ->assertOk()
        ->assertSee('Tạo Lead');
    $this->actingAs($manager)
        ->get(route('leads.show', $owned))
        ->assertOk()
        ->assertSee($owned->full_name);
    $this->actingAs($manager)->get(route('leads.edit', $owned))->assertOk();
    $this->actingAs($manager)->get(route('leads.show', $outside))->assertNotFound();
    $this->actingAs($manager)->get(route('leads.edit', $outside))->assertNotFound();

    $this->actingAs($viewer)
        ->get(route('leads.show', $owned))
        ->assertOk()
        ->assertDontSee('Chỉnh sửa');
    $this->actingAs($viewer)->get(route('leads.create'))->assertForbidden();
    $this->actingAs($viewer)->get(route('leads.edit', $owned))->assertForbidden();
});

it('creates a normalized lead with source tags owner department and audit', function (): void {
    $department = Department::factory()->create();
    $admin = p306Actor('admin');
    $owner = p306Actor('sales', $department);
    $source = LeadSource::factory()->create();
    $tags = Tag::factory()->count(2)->create();

    Livewire::actingAs($admin)
        ->test(LeadEditor::class)
        ->set('form.fullName', '  Nguyễn Khách Hàng  ')
        ->set('form.email', '  CUSTOMER@EXAMPLE.COM  ')
        ->set('form.phone', ' 0901234567 ')
        ->set('form.companyName', ' Công ty Mới ')
        ->set('form.website', 'https://example.com')
        ->set('form.sourceId', (string) $source->getKey())
        ->set('form.ownerId', (string) $owner->getKey())
        ->set('form.priority', LeadPriority::High->value)
        ->set('form.estimatedValue', '125000000')
        ->set('form.tagIds', $tags->modelKeys())
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->where('email', 'customer@example.com')->sole();

    expect($lead->full_name)->toBe('Nguyễn Khách Hàng')
        ->and($lead->phone)->toBe('0901234567')
        ->and($lead->company_name)->toBe('Công ty Mới')
        ->and($lead->lead_source_id)->toBe($source->getKey())
        ->and($lead->owner_id)->toBe($owner->getKey())
        ->and($lead->department_id)->toBe($department->getKey())
        ->and($lead->status)->toBe(LeadStatus::New)
        ->and($lead->priority)->toBe(LeadPriority::High)
        ->and($lead->created_by)->toBe($admin->getKey())
        ->and($lead->updated_by)->toBe($admin->getKey())
        ->and($lead->tags()->pluck('tags.id')->sort()->values()->all())->toBe($tags->modelKeys());

    $activity = Activity::query()->where('subject_type', Lead::class)->where('subject_id', $lead->getKey())->sole();

    expect($activity->event)->toBe('created')
        ->and($activity->description)->toBe('Tạo Lead')
        ->and($activity->causer_id)->toBe($admin->getKey())
        ->and($activity->properties->get('old'))->toBeNull()
        ->and($activity->properties->get('new')['owner_id'])->toBe($owner->getKey())
        ->and($activity->properties->get('new')['tag_ids'])->toBe($tags->modelKeys());
});

it('automatically assigns sales-created leads to the actor', function (): void {
    $department = Department::factory()->create();
    $sales = p306Actor('sales', $department);
    $outsider = p306Actor('sales', Department::factory()->create());

    Livewire::actingAs($sales)
        ->test(LeadEditor::class)
        ->assertSet('form.ownerId', (string) $sales->getKey())
        ->assertSee('Lead được giữ cho chính bạn')
        ->set('form.fullName', 'Lead của Sales')
        ->set('form.ownerId', '')
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->where('full_name', 'Lead của Sales')->sole();

    expect($lead->owner_id)->toBe($sales->getKey())
        ->and($lead->department_id)->toBe($department->getKey());

    Livewire::actingAs($sales)
        ->test(LeadEditor::class)
        ->set('form.fullName', 'Lead sai phạm vi')
        ->set('form.ownerId', (string) $outsider->getKey())
        ->call('save')
        ->assertForbidden();
});

it('limits manager assignment to active users in the same department', function (): void {
    $departmentA = Department::factory()->create(['code' => 'SALES-A']);
    $departmentB = Department::factory()->create(['code' => 'SALES-B']);
    $manager = p306Actor('sales-manager', $departmentA, ['name' => 'Manager A']);
    $colleague = p306Actor('sales', $departmentA, ['name' => 'Colleague A']);
    $outsider = p306Actor('sales', $departmentB, ['name' => 'Outsider B']);

    Livewire::actingAs($manager)
        ->test(LeadEditor::class)
        ->assertSee('Colleague A')
        ->assertDontSee('Outsider B')
        ->set('form.fullName', 'Lead phòng A')
        ->set('form.ownerId', (string) $colleague->getKey())
        ->call('save')
        ->assertHasNoErrors();

    expect(Lead::query()->where('full_name', 'Lead phòng A')->sole()->department_id)
        ->toBe($departmentA->getKey());

    Livewire::actingAs($manager)
        ->test(LeadEditor::class)
        ->set('form.fullName', 'Lead phòng B trái phép')
        ->set('form.ownerId', (string) $outsider->getKey())
        ->call('save')
        ->assertForbidden();
});

it('updates lead fields tags assignment and audit without changing status', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $admin = p306Actor('admin');
    $oldOwner = p306Actor('sales', $departmentA);
    $newOwner = p306Actor('sales', $departmentB);
    $oldTag = Tag::factory()->create();
    $newTags = Tag::factory()->count(2)->create();
    $lead = Lead::factory()->ownedBy($oldOwner)->create([
        'full_name' => 'Tên cũ',
        'status' => LeadStatus::Qualified,
        'created_by' => $oldOwner->getKey(),
        'updated_by' => $oldOwner->getKey(),
    ]);
    $lead->tags()->attach($oldTag);

    Livewire::actingAs($admin)
        ->test(LeadEditor::class, ['leadId' => $lead->getKey()])
        ->assertSet('form.fullName', 'Tên cũ')
        ->set('form.fullName', 'Tên đã cập nhật')
        ->set('form.ownerId', (string) $newOwner->getKey())
        ->set('form.tagIds', $newTags->modelKeys())
        ->set('form.notes', 'Nội dung mới')
        ->call('save')
        ->assertHasNoErrors();

    $lead->refresh();

    expect($lead->full_name)->toBe('Tên đã cập nhật')
        ->and($lead->owner_id)->toBe($newOwner->getKey())
        ->and($lead->department_id)->toBe($departmentB->getKey())
        ->and($lead->status)->toBe(LeadStatus::Qualified)
        ->and($lead->updated_by)->toBe($admin->getKey())
        ->and($lead->tags()->pluck('tags.id')->sort()->values()->all())->toBe($newTags->modelKeys());

    $activity = Activity::query()->where('subject_type', Lead::class)->where('subject_id', $lead->getKey())->sole();

    expect($activity->event)->toBe('updated')
        ->and($activity->properties->get('old')['owner_id'])->toBe($oldOwner->getKey())
        ->and($activity->properties->get('new')['owner_id'])->toBe($newOwner->getKey())
        ->and($activity->properties->get('new')['status'])->toBe(LeadStatus::Qualified->value);
});

it('validates contact taxonomy assignment priority value and tag input', function (): void {
    $admin = p306Actor('admin');
    $inactiveSource = LeadSource::factory()->inactive()->create();
    $inactiveTag = Tag::factory()->inactive()->create();
    $inactiveOwner = p306Actor('sales', Department::factory()->create(), ['is_active' => false]);

    Livewire::actingAs($admin)
        ->test(LeadEditor::class)
        ->set('form.fullName', '')
        ->set('form.email', 'INVALID EMAIL')
        ->set('form.website', 'not-a-url')
        ->set('form.sourceId', (string) $inactiveSource->getKey())
        ->set('form.ownerId', (string) $inactiveOwner->getKey())
        ->set('form.priority', 'invalid')
        ->set('form.estimatedValue', '-1')
        ->set('form.tagIds', [$inactiveTag->getKey()])
        ->call('save')
        ->assertHasErrors([
            'form.fullName' => 'required',
            'form.email' => 'email',
            'form.website' => 'url',
            'form.sourceId' => 'exists',
            'form.ownerId' => 'exists',
            'form.priority',
            'form.estimatedValue' => 'min',
            'form.tagIds.0' => 'exists',
        ]);

    expect(Lead::query()->count())->toBe(0)
        ->and(Activity::query()->where('subject_type', Lead::class)->count())->toBe(0);
});

it('renders detail data and hides mutation actions from viewers', function (): void {
    $department = Department::factory()->create(['name' => 'Kinh doanh', 'code' => 'SALES']);
    $owner = p306Actor('sales', $department, ['name' => 'Người phụ trách']);
    $viewer = p306Actor('viewer');
    $source = LeadSource::factory()->create(['name' => 'Website']);
    $tag = Tag::factory()->create(['name' => 'VIP']);
    $lead = Lead::factory()->ownedBy($owner)->create([
        'lead_source_id' => $source->getKey(),
        'full_name' => 'Lead chi tiết',
        'company_name' => 'Công ty Chi Tiết',
        'notes' => 'Nhu cầu triển khai CRM',
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);
    $lead->tags()->attach($tag);

    $this->actingAs($viewer)
        ->get(route('leads.show', $lead))
        ->assertOk()
        ->assertSeeText([
            'Lead chi tiết', 'Công ty Chi Tiết', 'Nhu cầu triển khai CRM', 'Website',
            'VIP', 'Người phụ trách', 'Kinh doanh (SALES)',
        ])
        ->assertDontSee('Chỉnh sửa');
});
