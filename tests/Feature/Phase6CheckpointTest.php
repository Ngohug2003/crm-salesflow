<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\TaskFilterData;
use App\Enums\ActivityType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Livewire\Customers\CustomerAttachmentManager;
use App\Livewire\Tasks\TaskDetailModal;
use App\Livewire\Tasks\TaskKanban;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepository;
use App\Services\CustomerTimelineService;
use App\Services\TaskManagementService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class Phase6CheckpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('activities.view');
        Permission::findOrCreate('activities.create');
        Permission::findOrCreate('activities.update');
        Permission::findOrCreate('activities.delete');

        Permission::findOrCreate('tasks.view');
        Permission::findOrCreate('tasks.create');
        Permission::findOrCreate('tasks.update');
        Permission::findOrCreate('tasks.delete');
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_phase6_end_to_end_workflow(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('sales');

        /** @var Company $company */
        $company = Company::factory()->create(['owner_id' => $user->id, 'created_by' => $user->id]);

        // 1. Tạo Activity trên Company
        /** @var Activity $activity */
        $activity = Activity::query()->create([
            'title' => 'Gặp khách hàng demo giải pháp',
            'activity_type' => ActivityType::Meeting->value,
            'performed_at' => now(),
            'duration_minutes' => 45,
            'location' => 'Văn phòng Công ty',
            'subject_type' => Company::class,
            'subject_id' => $company->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('activities', ['id' => $activity->id]);

        // 2. Tạo Task có Checklist và Comment
        /** @var Task $task */
        $task = Task::query()->create([
            'title' => 'Gửi dự thảo Hợp đồng kinh tế',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'due_date' => now()->addDay(),
            'reminder_at' => now()->subMinute(),
            'subject_type' => Company::class,
            'subject_id' => $company->id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskDetailModal::class)
            ->call('openModal', $task->id)
            ->set('newChecklistTitle', 'Rà soát điều khoản thanh toán')
            ->call('addChecklistItem')
            ->set('newCommentContent', 'Đã chốt xong điều khoản bảo hành 12 tháng.')
            ->call('addComment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_checklists', ['task_id' => $task->id, 'title' => 'Rà soát điều khoản thanh toán']);
        $this->assertDatabaseHas('task_comments', ['task_id' => $task->id, 'content' => 'Đã chốt xong điều khoản bảo hành 12 tháng.']);

        // 3. Chuyển cột trạng thái trên Kanban Board
        Livewire::actingAs($user)
            ->test(TaskKanban::class)
            ->call('moveTask', $task->id, 'in_progress');

        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);

        // 4. Chạy Console Command phát thông báo Nhắc hạn
        $this->artisan('tasks:send-reminders')
            ->assertSuccessful();

        $this->assertNotNull($task->fresh()->reminder_sent_at);

        // 5. Kiểm tra Customer Timeline tích hợp đầy đủ Activity và Task
        /** @var CustomerTimelineService $timelineService */
        $timelineService = app(CustomerTimelineService::class);
        $timelineItems = $timelineService->timelineForModel($user, $company);

        $this->assertNotEmpty($timelineItems);
    }

    public function test_task_repository_and_policy_enforce_owned_department_and_read_only_scopes(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $managerA = User::factory()->create(['department_id' => $departmentA->id]);
        $managerA->assignRole('sales-manager');
        $salesA = User::factory()->create(['department_id' => $departmentA->id]);
        $salesA->assignRole('sales');
        $salesB = User::factory()->create(['department_id' => $departmentB->id]);
        $salesB->assignRole('sales');
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        $viewer->givePermissionTo(['tasks.update', 'tasks.delete']);

        $taskA = Task::query()->create([
            'title' => 'Công việc phòng A',
            'created_by' => $salesA->id,
            'assigned_to' => $salesA->id,
        ]);
        $taskB = Task::query()->create([
            'title' => 'Công việc phòng B',
            'created_by' => $salesB->id,
            'assigned_to' => $salesB->id,
        ]);

        /** @var TaskRepository $repository */
        $repository = app(TaskRepository::class);

        $managerIds = collect($repository->paginateForUser($managerA, new TaskFilterData, 20)->items())
            ->pluck('id');
        $salesIds = collect($repository->paginateForUser($salesA, new TaskFilterData, 20)->items())
            ->pluck('id');

        $this->assertTrue($managerIds->contains($taskA->id));
        $this->assertFalse($managerIds->contains($taskB->id));
        $this->assertSame([$taskA->id], $salesIds->all());
        $this->assertTrue($viewer->can('view', $taskA));
        $this->assertFalse($viewer->can('update', $taskA));
        $this->assertFalse($viewer->can('delete', $taskA));
    }

    public function test_task_subject_must_be_visible_to_the_actor(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('sales');
        $otherOwner = User::factory()->create();
        $otherOwner->assignRole('sales');
        $otherCompany = Company::factory()->create([
            'owner_id' => $otherOwner->id,
            'created_by' => $otherOwner->id,
        ]);

        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);

        $this->expectException(ModelNotFoundException::class);
        $service->create($actor, [
            'title' => 'Không được gắn đối tượng ngoài scope',
            'subject_type' => Company::class,
            'subject_id' => $otherCompany->id,
        ]);
    }

    public function test_attachment_component_rejects_task_outside_actor_scope(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('sales');
        $outsider = User::factory()->create();
        $outsider->assignRole('sales');
        $task = Task::query()->create([
            'title' => 'Tệp nội bộ của chủ sở hữu',
            'created_by' => $owner->id,
            'assigned_to' => $owner->id,
        ]);

        Livewire::actingAs($outsider)
            ->test(CustomerAttachmentManager::class, [
                'modelType' => Task::class,
                'modelId' => $task->id,
            ])
            ->assertForbidden();
    }
}
