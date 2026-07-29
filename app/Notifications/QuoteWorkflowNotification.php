<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

final class QuoteWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Quote $quote,
        private readonly string $title,
        private readonly string $message,
        private readonly string $type,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'quote_id' => $this->quote->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'action_url' => route('opportunities.show', $this->quote->opportunity_id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
