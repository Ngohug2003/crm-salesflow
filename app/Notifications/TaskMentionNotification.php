<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class TaskMentionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public User $mentionedBy,
        public string $commentContent,
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
            'title' => "{$this->mentionedBy->name} đã nhắc đến bạn trong thảo luận công việc",
            'message' => "Công việc: {$this->task->title} — \"".mb_strimwidth($this->commentContent, 0, 80, '...').'"',
            'action_url' => route('tasks.show', $this->task->id),
        ];
    }
}
