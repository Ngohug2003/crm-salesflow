<?php

declare(strict_types=1);

namespace App\Broadcasting;

use App\Models\Pipeline;
use App\Models\User;

final class PipelineChannel
{
    public function join(User $user, int $pipelineId): bool
    {
        if (! $user->hasAnyPermission(['opportunities.view', 'opportunities.view-all'])) {
            return false;
        }

        return Pipeline::query()->where('id', $pipelineId)->where('is_active', true)->exists();
    }
}
