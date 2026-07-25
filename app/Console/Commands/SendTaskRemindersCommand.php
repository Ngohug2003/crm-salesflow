<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\SystemAuditService;
use Illuminate\Console\Command;

final class SendTaskRemindersCommand extends Command
{
    protected $signature = 'tasks:send-reminders';

    protected $description = 'Phát hiện và gửi thông báo nhắc hạn công việc đến người thực hiện';

    public function handle(SystemAuditService $audit): int
    {
        $dueTasks = Task::query()
            ->with(['assignee', 'creator'])
            ->whereNull('completed_at')
            ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now())
            ->whereNull('reminder_sent_at')
            ->get();

        $count = 0;

        foreach ($dueTasks as $task) {
            $task->update(['reminder_sent_at' => now()]);

            $assigneeName = $task->assignee?->name ?: 'Người thực hiện';
            $this->info("Đã gửi nhắc hạn cho công việc #{$task->id} '{$task->title}' tới {$assigneeName}.");

            $audit->record(
                actor: $task->creator,
                subject: $task,
                event: 'reminder_sent',
                description: "Đã gửi thông báo nhắc hạn công việc '{$task->title}' cho {$assigneeName}",
                old: null,
                new: ['reminder_sent_at' => (string) $task->reminder_sent_at],
            );

            $count++;
        }

        $this->info("Hoàn tất xử lý. Đã gửi {$count} thông báo nhắc hạn công việc.");

        return Command::SUCCESS;
    }
}
