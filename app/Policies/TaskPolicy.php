<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\DataScope;
use App\Models\Task;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class TaskPolicy
{
    public function __construct(
        private DataScopeService $dataScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasPermissionTo('tasks.view')
            && $this->isVisible($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('tasks.create')
            && $this->dataScope->canWrite($user);
    }

    public function update(User $user, Task $task): bool
    {
        if (! $user->hasPermissionTo('tasks.update') || ! $this->dataScope->canWrite($user)) {
            return false;
        }

        return $this->isVisible($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        if (! $user->hasPermissionTo('tasks.delete') || ! $this->dataScope->canWrite($user)) {
            return false;
        }

        return $this->isVisible($user, $task);
    }

    private function isVisible(User $user, Task $task): bool
    {
        $scope = $this->dataScope->resolve($user);

        if ($scope === DataScope::All || $scope === DataScope::ReadOnly) {
            return true;
        }

        if ($scope === DataScope::Owned) {
            return (int) $user->getKey() === (int) $task->assigned_to
                || (int) $user->getKey() === (int) $task->created_by
                || $task->assignees()->whereKey($user->getKey())->exists();
        }

        if ($user->department_id === null) {
            return false;
        }

        return User::query()
            ->whereKey(array_filter([$task->assigned_to, $task->created_by]))
            ->where('department_id', $user->department_id)
            ->exists()
            || $task->assignees()
                ->where('department_id', $user->department_id)
                ->exists();
    }
}
