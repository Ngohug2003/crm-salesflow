<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tasks\TaskShow;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskMentionNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

final class TaskMentionAndDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_it_renders_dedicated_task_show_page(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('sales');

        /** @var Task $task */
        $task = Task::query()->create([
            'title' => 'Trang chi tiết Task chuyên sâu',
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskShow::class, ['taskId' => $task->id])
            ->assertSee('Trang chi tiết Task chuyên sâu')
            ->assertSee('Thảo luận thời gian thực');
    }

    public function test_it_sends_notification_when_user_is_mentioned_in_comment(): void
    {
        Notification::fake();

        $department = Department::factory()->create();

        /** @var User $actor */
        $actor = User::factory()->create([
            'name' => 'Người Giao Task',
            'department_id' => $department->id,
        ]);
        $actor->assignRole('sales-manager');

        /** @var User $recipient */
        $recipient = User::factory()->create([
            'name' => 'Tạ Ngọc Hân',
            'department_id' => $department->id,
        ]);
        $recipient->assignRole('sales');

        /** @var Task $task */
        $task = Task::query()->create([
            'title' => 'Thảo luận dự án phần mềm',
            'created_by' => $actor->id,
            'assigned_to' => $actor->id,
        ]);
        $task->assignees()->attach($recipient->id);

        Livewire::actingAs($actor)
            ->test(TaskShow::class, ['taskId' => $task->id])
            ->set('newCommentContent', 'Nhờ @Tạ Ngọc Hân vào kiểm tra lại thiết kế nhé!')
            ->call('addComment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $actor->id,
            'content' => 'Nhờ @Tạ Ngọc Hân vào kiểm tra lại thiết kế nhé!',
        ]);

        Notification::assertSentTo(
            $recipient,
            TaskMentionNotification::class
        );
    }

    public function test_mention_does_not_expand_task_visibility_or_update_permission(): void
    {
        Notification::fake();

        /** @var User $creator */
        $creator = User::factory()->create(['name' => 'Trần Văn A']);
        $creator->assignRole('sales');

        /** @var User $mentionedUser */
        $mentionedUser = User::factory()->create(['name' => 'Nguyễn Thị B']);
        $mentionedUser->assignRole('sales');

        /** @var Task $task */
        $task = Task::query()->create([
            'title' => 'Nhiệm vụ nghiên cứu thị trường',
            'created_by' => $creator->id,
            'assigned_to' => $creator->id,
        ]);

        // $creator đăng comment @mention $mentionedUser
        Livewire::actingAs($creator)
            ->test(TaskShow::class, ['taskId' => $task->id])
            ->set('newCommentContent', 'Mời @Nguyễn Thị B vào đóng góp ý kiến.')
            ->call('addComment')
            ->assertHasNoErrors();

        $this->assertFalse($mentionedUser->can('view', $task));
        $this->assertFalse($mentionedUser->can('update', $task));
        $this->actingAs($mentionedUser)
            ->get(route('tasks.show', $task->id))
            ->assertNotFound();

        Notification::assertNotSentTo($mentionedUser, TaskMentionNotification::class);
    }
}
