<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OpportunityRiskSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

final class OpportunityRiskNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly OpportunityRiskSnapshot $snapshot) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $opportunity = $this->snapshot->opportunity;
        $levelStr = strtoupper((string) $this->snapshot->level);

        return [
            'opportunity_id' => $opportunity->id,
            'risk_snapshot_id' => $this->snapshot->id,
            'title' => "Cơ hội rủi ro [{$levelStr}]: {$opportunity->title}",
            'message' => 'Hệ thống phát hiện tín hiệu rủi ro mới cần xử lý.',
            'type' => 'opportunity_risk',
            'risk_level' => $this->snapshot->level,
            'action_url' => route('opportunities.show', $opportunity->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
