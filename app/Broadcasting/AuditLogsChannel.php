<?php

declare(strict_types=1);

namespace App\Broadcasting;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

final class AuditLogsChannel
{
    public function join(User $user): bool
    {
        return $user->is_active && Gate::forUser($user)->allows('viewAny', Activity::class);
    }
}
