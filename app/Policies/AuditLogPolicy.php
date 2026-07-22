<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

final class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin')
            && $user->can('audit-logs.view')
            && $user->department()->where('code', 'IT')->exists();
    }

    public function view(User $user, Activity $activity): bool
    {
        return $this->viewAny($user);
    }
}
