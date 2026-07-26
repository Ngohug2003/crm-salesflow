<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tasks\TaskDetailModal;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class TaskCollaborationTest extends TestCase
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

    public function test_it_creates_toggles_and_deletes_task_checklist_items(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('sales');

        /** @var Task $task */
        $task = Task::query()->create([
            'title' => 'Chuẩn bị hồ sơ dự thầu',
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskDetailModal::class)
            ->call('openModal', $task->id)
            ->set('newChecklistTitle', 'Kiểm tra báo cáo tài chính')
            ->call('addChecklistItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_checklists', [
            'task_id' => $task->id,
            'title' => 'Kiểm tra báo cáo tài chính',
            'is_completed' => false,
        ]);

        /** @var TaskChecklist $item */
        $item = TaskChecklist::query()->where('task_id', $task->id)->firstOrFail();

        Livewire::actingAs($user)
            ->test(TaskDetailModal::class)
            ->call('openModal', $task->id)
            ->call('toggleChecklistItem', $item->id);

        $this->assertTrue($item->fresh()->is_completed);

        Livewire::actingAs($user)
            ->test(TaskDetailModal::class)
            ->call('openModal', $task->id)
            ->call('deleteChecklistItem', $item->id);

        $this->assertDatabaseMissing('task_checklists', ['id' => $item->id]);
    }

    public function test_it_adds_and_deletes_task_comments(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('sales');

        /** @var Task $task */
        $task = Task::query()->create([
            'title' => 'Xem xét đề xuất giải pháp',
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskDetailModal::class)
            ->call('openModal', $task->id)
            ->set('newCommentContent', 'Đã cập nhật xong phương án kỹ thuật mới.')
            ->call('addComment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => 'Đã cập nhật xong phương án kỹ thuật mới.',
        ]);

        /** @var TaskComment $comment */
        $comment = TaskComment::query()->where('task_id', $task->id)->firstOrFail();

        Livewire::actingAs($user)
            ->test(TaskDetailModal::class)
            ->call('openModal', $task->id)
            ->call('deleteComment', $comment->id);

        $this->assertSoftDeleted('task_comments', ['id' => $comment->id]);
    }
}
