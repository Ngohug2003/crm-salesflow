<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class ActivityPolicy
{
    public function __construct(
        private DataScopeService $dataScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('activities.view');
    }

    public function view(User $user, Activity $activity): bool
    {
        if (! $user->hasPermissionTo('activities.view')) {
            return false;
        }

        return $this->dataScope->allows($user, $activity->user_id, null);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('activities.create');
    }

    public function update(User $user, Activity $activity): bool
    {
        if (! $user->hasPermissionTo('activities.update')) {
            return false;
        }

        return (int) $user->id === (int) $activity->user_id
            || (int) $user->id === (int) $activity->created_by
            || $this->dataScope->allows($user, $activity->user_id, null);
    }

    public function delete(User $user, Activity $activity): bool
    {
        if (! $user->hasPermissionTo('activities.delete')) {
            return false;
        }

        return (int) $user->id === (int) $activity->user_id
            || (int) $user->id === (int) $activity->created_by
            || $this->dataScope->allows($user, $activity->user_id, null);
    }
}
