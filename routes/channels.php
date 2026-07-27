<?php

use App\Broadcasting\AuditLogsChannel;
use App\Broadcasting\PipelineChannel;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int|string $id): bool {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('audit-logs', AuditLogsChannel::class);
Broadcast::channel('pipelines.{pipelineId}', PipelineChannel::class);

// System Console realtime log stream — only users with system-console.view permission
Broadcast::channel('system-console', function (User $user): bool {
    return $user->can('system-console.view');
});
