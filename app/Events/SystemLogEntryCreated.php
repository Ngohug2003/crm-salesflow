<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast a single system log entry to the system-console private channel.
 * Uses ShouldBroadcastNow to skip the queue for true realtime delivery.
 */
final class SystemLogEntryCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array{id: string, timestamp: string, level: string, module: string, action: string, status_code: int|null, user_id: int|null, duration_ms: float|null, request_id: string, event: string, line: string}  $entry
     */
    public function __construct(public readonly array $entry) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('system-console')];
    }

    public function broadcastAs(): string
    {
        return 'SystemLogEntryCreated';
    }
}
