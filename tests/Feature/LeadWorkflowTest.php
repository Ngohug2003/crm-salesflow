<?php

use App\Enums\LeadStatus;
use App\Events\LeadAssigned;
use App\Events\LeadStatusChanged;
use App\Exceptions\LeadWorkflowException;
use App\Livewire\Leads\LeadEditor;
use App\Livewire\Leads\LeadWorkflow;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadAssignmentHistory;
use App\Models\LeadStatusHistory;
use App\Models\User;
use App\Repositories\Contracts\LeadWorkflowRepository;
use App\Services\LeadAssignmentService;
use App\Services\LeadStatusTransitionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function p307Actor(string $role, ?Department $department = null, array $attributes = []): User
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

it('defines the approved transition matrix and keeps converted terminal', function (): void {
    $service = app(LeadStatusTransitionService::class);

    expect(array_map(fn (LeadStatus $status): string => $status->value, $service->availableTransitions(LeadStatus::New)))
        ->toBe(['contacted', 'unqualified', 'lost'])
        ->and(array_map(fn (LeadStatus $status): string => $status->value, $service->availableTransitions(LeadStatus::Contacted)))
        ->toBe(['qualified', 'unqualified', 'lost'])
        ->and(array_map(fn (LeadStatus $status): string => $status->value, $service->availableTransitions(LeadStatus::Qualified)))
        ->toBe(['contacted', 'lost'])
        ->and($service->availableTransitions(LeadStatus::Unqualified))->toBe([LeadStatus::New])
        ->and($service->availableTransitions(LeadStatus::Lost))->toBe([LeadStatus::New])
        ->and($service->availableTransitions(LeadStatus::Converted))->toBe([]);
});

it('assigns an active scoped owner atomically with history audit and after-commit event', function (): void {
    Event::fake([LeadAssigned::class]);
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $admin = p307Actor('admin');
    $oldOwner = p307Actor('sales', $departmentA);
    $newOwner = p307Actor('sales', $departmentB);
    $lead = Lead::factory()->ownedBy($oldOwner)->create();

    $saved = app(LeadAssignmentService::class)->assign(
        $admin,
        $lead->getKey(),
        $newOwner->getKey(),
        ' Chuyển cho nhóm miền Nam ',
    );

    expect($saved->owner_id)->toBe($newOwner->getKey())
        ->and($saved->department_id)->toBe($departmentB->getKey())
        ->and($saved->updated_by)->toBe($admin->getKey());

    $history = LeadAssignmentHistory::query()->sole();

    expect($history->previous_owner_id)->toBe($oldOwner->getKey())
        ->and($history->new_owner_id)->toBe($newOwner->getKey())
        ->and($history->previous_department_id)->toBe($departmentA->getKey())
        ->and($history->new_department_id)->toBe($departmentB->getKey())
        ->and($history->changed_by)->toBe($admin->getKey())
        ->and($history->reason)->toBe('Chuyển cho nhóm miền Nam');

    $activity = Activity::query()->where('subject_type', Lead::class)->where('subject_id', $lead->getKey())->sole();

    expect($activity->event)->toBe('assigned')
        ->and($activity->properties->get('old')['owner_id'])->toBe($oldOwner->getKey())
        ->and($activity->properties->get('new')['owner_id'])->toBe($newOwner->getKey())
        ->and($activity->properties->get('reason'))->toBe('Chuyển cho nhóm miền Nam');

    Event::assertDispatched(
        LeadAssigned::class,
        fn (LeadAssigned $event): bool => $event instanceof ShouldDispatchAfterCommit
            && $event->leadId === $lead->getKey()
            && $event->previousOwnerId === $oldOwner->getKey()
            && $event->newOwnerId === $newOwner->getKey(),
    );
});

it('blocks assignment outside manager scope and all assignment by sales', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $manager = p307Actor('sales-manager', $departmentA);
    $sales = p307Actor('sales', $departmentA);
    $outsider = p307Actor('sales', $departmentB);
    $lead = Lead::factory()->ownedBy($sales)->create();
    $service = app(LeadAssignmentService::class);

    expect(fn () => $service->assign($manager, $lead->getKey(), $outsider->getKey(), null))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $service->assign($sales, $lead->getKey(), $manager->getKey(), null))
        ->toThrow(AuthorizationException::class);

    expect($lead->refresh()->owner_id)->toBe($sales->getKey())
        ->and(LeadAssignmentHistory::query()->count())->toBe(0)
        ->and(Activity::query()->where('subject_type', Lead::class)->count())->toBe(0);
});

it('rejects no-op assignment and direct owner changes through the general editor', function (): void {
    $department = Department::factory()->create();
    $admin = p307Actor('admin');
    $owner = p307Actor('sales', $department);
    $other = p307Actor('sales', $department);
    $lead = Lead::factory()->ownedBy($owner)->create();

    Livewire::actingAs($admin)
        ->test(LeadWorkflow::class, ['leadId' => $lead->getKey()])
        ->call('openAssign')
        ->assertSee('Lý do của lịch sử cũ không thể chỉnh sửa')
        ->assertSee('disabled', false)
        ->set('assignmentReason', 'Chỉ sửa lý do')
        ->set('ownerId', (string) $owner->getKey())
        ->call('assign')
        ->assertHasErrors(['ownerId'])
        ->assertSee('Hãy chọn người phụ trách khác trước khi lưu phân công.');

    Livewire::actingAs($admin)
        ->test(LeadEditor::class, ['leadId' => $lead->getKey()])
        ->assertDontSee('wire:model="form.ownerId"', false)
        ->set('form.ownerId', (string) $other->getKey())
        ->call('save')
        ->assertForbidden();

    expect($lead->refresh()->owner_id)->toBe($owner->getKey())
        ->and(LeadAssignmentHistory::query()->count())->toBe(0);
});

it('changes status with history audit and after-commit event', function (): void {
    Event::fake([LeadStatusChanged::class]);
    $sales = p307Actor('sales', Department::factory()->create());
    $lead = Lead::factory()->ownedBy($sales)->create(['status' => LeadStatus::New]);

    $saved = app(LeadStatusTransitionService::class)->transition(
        $sales,
        $lead->getKey(),
        LeadStatus::Contacted->value,
        ' Đã gọi lần đầu ',
    );

    expect($saved->status)->toBe(LeadStatus::Contacted)
        ->and($saved->updated_by)->toBe($sales->getKey());

    $history = LeadStatusHistory::query()->sole();

    expect($history->from_status)->toBe(LeadStatus::New)
        ->and($history->to_status)->toBe(LeadStatus::Contacted)
        ->and($history->changed_by)->toBe($sales->getKey())
        ->and($history->reason)->toBe('Đã gọi lần đầu');

    $activity = Activity::query()->where('subject_type', Lead::class)->where('subject_id', $lead->getKey())->sole();

    expect($activity->event)->toBe('status_changed')
        ->and($activity->properties->get('old')['status'])->toBe(LeadStatus::New->value)
        ->and($activity->properties->get('new')['status'])->toBe(LeadStatus::Contacted->value);

    Event::assertDispatched(
        LeadStatusChanged::class,
        fn (LeadStatusChanged $event): bool => $event instanceof ShouldDispatchAfterCommit
            && $event->leadId === $lead->getKey()
            && $event->fromStatus === LeadStatus::New->value
            && $event->toStatus === LeadStatus::Contacted->value,
    );
});

it('rejects invalid status transitions and requires reasons for rejected outcomes', function (): void {
    $sales = p307Actor('sales', Department::factory()->create());
    $lead = Lead::factory()->ownedBy($sales)->create(['status' => LeadStatus::New]);
    $service = app(LeadStatusTransitionService::class);

    expect(fn () => $service->transition($sales, $lead->getKey(), LeadStatus::Qualified->value, null))
        ->toThrow(LeadWorkflowException::class, 'Không thể chuyển')
        ->and(fn () => $service->transition($sales, $lead->getKey(), LeadStatus::Lost->value, null))
        ->toThrow(LeadWorkflowException::class, 'Vui lòng nhập lý do')
        ->and(fn () => $service->transition($sales, $lead->getKey(), LeadStatus::Converted->value, 'Thử bỏ qua conversion'))
        ->toThrow(LeadWorkflowException::class, 'Không thể chuyển');

    expect($lead->refresh()->status)->toBe(LeadStatus::New)
        ->and(LeadStatusHistory::query()->count())->toBe(0)
        ->and(Activity::query()->where('subject_type', Lead::class)->count())->toBe(0);
});

it('rolls back assignment when workflow history persistence fails', function (): void {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $admin = p307Actor('admin');
    $oldOwner = p307Actor('sales', $departmentA);
    $newOwner = p307Actor('sales', $departmentB);
    $lead = Lead::factory()->ownedBy($oldOwner)->create();
    $workflow = Mockery::mock(LeadWorkflowRepository::class);
    $workflow->shouldReceive('createAssignmentHistory')->once()->andThrow(new RuntimeException('History unavailable'));
    $this->app->instance(LeadWorkflowRepository::class, $workflow);

    expect(fn () => app(LeadAssignmentService::class)->assign(
        $admin,
        $lead->getKey(),
        $newOwner->getKey(),
        'Kiểm tra rollback',
    ))->toThrow(RuntimeException::class, 'History unavailable');

    expect($lead->refresh()->owner_id)->toBe($oldOwner->getKey())
        ->and($lead->department_id)->toBe($departmentA->getKey())
        ->and(LeadAssignmentHistory::query()->count())->toBe(0);
});

it('records initial workflow and renders a read-only unified timeline for viewers', function (): void {
    $department = Department::factory()->create();
    $admin = p307Actor('admin');
    $owner = p307Actor('sales', $department, ['name' => 'Sales Owner']);
    $viewer = p307Actor('viewer');

    Livewire::actingAs($admin)
        ->test(LeadEditor::class)
        ->set('form.fullName', 'Lead có timeline')
        ->set('form.ownerId', (string) $owner->getKey())
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->where('full_name', 'Lead có timeline')->sole();

    expect(LeadAssignmentHistory::query()->where('lead_id', $lead->getKey())->count())->toBe(1)
        ->and(LeadStatusHistory::query()->where('lead_id', $lead->getKey())->count())->toBe(1);

    Livewire::actingAs($viewer)
        ->test(LeadWorkflow::class, ['leadId' => $lead->getKey()])
        ->assertSee('Quy trình và lịch sử Lead')
        ->assertSee('Khởi tạo → Mới')
        ->assertSee('Chưa phân công → Sales Owner')
        ->assertDontSee('wire:submit="assign"', false)
        ->assertDontSee('wire:submit="changeStatus"', false);
});
