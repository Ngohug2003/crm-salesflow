<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\Tasks\TaskCalendar;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class TaskCalendarReminderTest extends TestCase
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

    public function test_it_renders_task_calendar_component(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->givePermissionTo(['tasks.view']);

        /** @var Task $task */
        $task = Task::query()->create([
            'title' => 'Họp tổng kết dự án',
            'status' => TaskStatus::Todo->value,
            'due_date' => now()->startOfMonth()->addDays(5),
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskCalendar::class)
            ->assertSee('Lịch Công việc & Hoạt động', false)
            ->assertSee('Họp tổng kết dự án');
    }

    public function test_it_sends_task_reminders_via_console_command(): void
    {
        Notification::fake();

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Task $task */
        $task = Task::query()->create([
            'title' => 'Gửi báo giá cho khách hàng',
            'status' => TaskStatus::Todo->value,
            'due_date' => now()->addHour(),
            'reminder_at' => now()->subMinutes(10),
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        $this->assertNull($task->reminder_sent_at);

        $this->artisan('tasks:send-reminders')
            ->assertSuccessful()
            ->expectsOutputToContain("Đã gửi nhắc hạn cho công việc #{$task->id}");

        $this->assertNotNull($task->fresh()->reminder_sent_at);

        Notification::assertSentTo(
            $user,
            TaskReminderNotification::class
        );

        $this->artisan('tasks:send-reminders')->assertSuccessful();
        Notification::assertSentToTimes($user, TaskReminderNotification::class, 1);
    }
}
