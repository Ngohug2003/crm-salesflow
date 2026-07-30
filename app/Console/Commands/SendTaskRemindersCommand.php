<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use App\Services\SystemAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class SendTaskRemindersCommand extends Command
{
    protected $signature = 'tasks:send-reminders';

    protected $description = 'Phát hiện và gửi thông báo nhắc hạn công việc đến người thực hiện';

    public function handle(SystemAuditService $audit): int
    {
        $dueTaskIds = Task::query()
            ->whereNull('completed_at')
            ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now())
            ->whereNull('reminder_sent_at')
            ->pluck('id');

        $count = 0;

        foreach ($dueTaskIds as $taskId) {
            $message = DB::transaction(function () use ($audit, $taskId): ?string {
                /** @var Task|null $task */
                $task = Task::query()
                    ->with(['assignee', 'assignees', 'creator'])
                    ->whereKey($taskId)
                    ->whereNull('completed_at')
                    ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
                    ->whereNotNull('reminder_at')
                    ->where('reminder_at', '<=', now())
                    ->whereNull('reminder_sent_at')
                    ->lockForUpdate()
                    ->first();

                if ($task === null) {
                    return null;
                }

                $recipients = collect([$task->assignee])
                    ->filter()
                    ->merge($task->assignees)
                    ->unique('id');

                foreach ($recipients as $recipient) {
                    $recipient->notify(new TaskReminderNotification($task));
                }

                $sentAt = now();
                $task->update(['reminder_sent_at' => $sentAt]);

                $names = $recipients->pluck('name')->implode(', ') ?: 'Người thực hiện';
                $audit->record(
                    actor: $task->creator,
                    subject: $task,
                    event: 'reminder_sent',
                    description: "Đã gửi thông báo nhắc hạn công việc '{$task->title}' cho {$names}",
                    old: null,
                    new: ['reminder_sent_at' => $sentAt->toIso8601String()],
                );

                return "Đã gửi nhắc hạn cho công việc #{$task->id} '{$task->title}' tới: {$names}.";
            });

            if ($message !== null) {
                $this->info($message);
                $count++;
            }
        }

        $this->info("Hoàn tất xử lý. Đã gửi {$count} thông báo nhắc hạn công việc.");

        return Command::SUCCESS;
    }
}
