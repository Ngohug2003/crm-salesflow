<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

final class OpportunityStageUpdatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Opportunity $opportunity,
        public ?int $fromStageId,
        public int $toStageId,
        public User $actor,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("pipelines.{$this->opportunity->pipeline_id}");
    }

    public function broadcastAs(): string
    {
        return 'OpportunityStageUpdated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'opportunity_id' => $this->opportunity->id,
            'opportunity_title' => $this->opportunity->title,
            'code' => $this->opportunity->code,
            'pipeline_id' => $this->opportunity->pipeline_id,
            'from_stage_id' => $this->fromStageId,
            'to_stage_id' => $this->toStageId,
            'amount' => (float) $this->opportunity->amount,
            'weighted_value' => $this->opportunity->weighted_value,
            'is_won' => $this->opportunity->is_won,
            'is_lost' => $this->opportunity->is_lost,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
