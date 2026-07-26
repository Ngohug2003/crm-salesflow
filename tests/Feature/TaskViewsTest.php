<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\Tasks\TaskKanban;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class TaskViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('tasks.view');
        Permission::findOrCreate('tasks.create');
        Permission::findOrCreate('tasks.update');
        Permission::findOrCreate('tasks.delete');
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_it_renders_task_kanban_component_and_moves_tasks_between_columns(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('sales');

        /** @var Task $task */
        $task = Task::query()->create([
            'title' => 'Xây dựng giao diện Kanban',
            'status' => TaskStatus::Todo->value,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskKanban::class)
            ->assertSee('Công việc — Kanban Board')
            ->assertSee('Xây dựng giao diện Kanban')
            ->call('moveTask', $task->id, 'in_progress')
            ->assertHasNoErrors();

        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);

        Livewire::actingAs($user)
            ->test(TaskKanban::class)
            ->call('moveTask', $task->id, 'completed');

        $this->assertSame(TaskStatus::Completed, $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
    }
}
