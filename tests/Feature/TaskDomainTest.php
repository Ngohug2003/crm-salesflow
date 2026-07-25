<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Livewire\Tasks\TaskList;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class TaskDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('tasks.view');
        Permission::findOrCreate('tasks.create');
        Permission::findOrCreate('tasks.update');
        Permission::findOrCreate('tasks.delete');
    }

    public function test_it_creates_task_with_postgresql_bigint_key_and_enums(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->givePermissionTo(['tasks.view', 'tasks.create']);

        /** @var Task $task */
        $task = Task::query()->create([
            'title' => 'Gọi điện tư vấn hợp đồng',
            'description' => 'Chuẩn bị báo giá chi tiết',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'due_date' => now()->addDays(2),
            'assigned_to' => $user->id,
            'created_by' => $user->id,
        ]);

        $this->assertIsInt($task->id);
        $this->assertSame(TaskStatus::Todo, $task->status);
        $this->assertSame(TaskPriority::High, $task->priority);
        $this->assertSame($user->id, $task->assignee?->id);
    }

    public function test_it_supports_polymorphic_relationships_with_company_and_opportunity(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Company $company */
        $company = Company::factory()->create(['owner_id' => $user->id, 'created_by' => $user->id]);
        /** @var Opportunity $opp */
        $opp = Opportunity::factory()->create(['owner_id' => $user->id, 'created_by' => $user->id]);

        /** @var Task $taskCompany */
        $taskCompany = Task::query()->create([
            'title' => 'Ký HĐ với công ty',
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::Urgent->value,
            'created_by' => $user->id,
            'subject_type' => Company::class,
            'subject_id' => $company->id,
        ]);

        /** @var Task $taskOpp */
        $taskOpp = Task::query()->create([
            'title' => 'Demo sản phẩm cho Opportunity',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::Medium->value,
            'created_by' => $user->id,
            'subject_type' => Opportunity::class,
            'subject_id' => $opp->id,
        ]);

        $this->assertInstanceOf(Company::class, $taskCompany->subject);
        $this->assertSame($company->id, $taskCompany->subject->id);

        $this->assertInstanceOf(Opportunity::class, $taskOpp->subject);
        $this->assertSame($opp->id, $taskOpp->subject->id);

        $this->assertCount(1, $company->tasks);
        $this->assertCount(1, $opp->tasks);
    }

    public function test_it_renders_task_list_component_and_supports_crud(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->givePermissionTo(['tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete']);

        Livewire::actingAs($user)
            ->test(TaskList::class)
            ->assertSee('Quản lý Công việc (Tasks)')
            ->set('title', 'Họp chiến lược Quý 3')
            ->set('assigneeId', $user->id)
            ->set('taskPriority', 'urgent')
            ->set('taskStatus', 'todo')
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Họp chiến lược Quý 3',
            'priority' => 'urgent',
            'created_by' => $user->id,
        ]);

        /** @var Task $createdTask */
        $createdTask = Task::query()->where('title', 'Họp chiến lược Quý 3')->firstOrFail();

        Livewire::actingAs($user)
            ->test(TaskList::class)
            ->call('toggleTaskStatus', $createdTask->id);

        $this->assertSame(TaskStatus::Completed, $createdTask->fresh()->status);
        $this->assertNotNull($createdTask->fresh()->completed_at);
    }
}
