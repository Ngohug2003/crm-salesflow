<?php

declare(strict_types=1);

namespace App\Policies;

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
        if (! $user->hasPermissionTo('tasks.view')) {
            return false;
        }

        return $this->dataScope->allows($user, $task->assigned_to, null)
            || $this->dataScope->allows($user, $task->created_by, null);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        if (! $user->hasPermissionTo('tasks.update')) {
            return false;
        }

        return $user->id === $task->assigned_to
            || $user->id === $task->created_by
            || $this->dataScope->allows($user, $task->assigned_to, null);
    }

    public function delete(User $user, Task $task): bool
    {
        if (! $user->hasPermissionTo('tasks.delete')) {
            return false;
        }

        return $user->id === $task->created_by
            || $this->dataScope->allows($user, $task->assigned_to, null);
    }
}
