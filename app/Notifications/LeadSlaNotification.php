<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class LeadSlaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public string $title,
        public string $message,
        public string $type = 'sla_overdue',
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
            'lead_id' => $this->lead->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'action_url' => route('leads.show', $this->lead->id),
        ];
    }
}
