<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class TaskReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => 'Nhắc hạn công việc: '.$this->task->title,
            'message' => 'Công việc "'.$this->task->title.'" của bạn sắp/đã đến hạn xử lý.',
            'due_date' => $this->task->due_date?->format('d/m/Y H:i'),
            'priority' => $this->task->priority->value,
            'action_url' => route('tasks.show', $this->task->id),
        ];
    }
}
