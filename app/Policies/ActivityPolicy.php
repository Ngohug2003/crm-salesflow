<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\DataScope;
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

        return $this->isVisible($user, $activity);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('activities.create')
            && $this->dataScope->canWrite($user);
    }

    public function update(User $user, Activity $activity): bool
    {
        if (! $user->hasPermissionTo('activities.update') || ! $this->dataScope->canWrite($user)) {
            return false;
        }

        return $this->isVisible($user, $activity);
    }

    public function delete(User $user, Activity $activity): bool
    {
        if (! $user->hasPermissionTo('activities.delete') || ! $this->dataScope->canWrite($user)) {
            return false;
        }

        return $this->isVisible($user, $activity);
    }

    private function isVisible(User $user, Activity $activity): bool
    {
        $scope = $this->dataScope->resolve($user);

        if ($scope === DataScope::All || $scope === DataScope::ReadOnly) {
            return true;
        }

        if ($scope === DataScope::Owned) {
            return (int) $user->getKey() === (int) $activity->user_id
                || (int) $user->getKey() === (int) $activity->created_by;
        }

        if ($user->department_id === null) {
            return false;
        }

        return User::query()
            ->whereKey(array_filter([$activity->user_id, $activity->created_by]))
            ->where('department_id', $user->department_id)
            ->exists();
    }
}
