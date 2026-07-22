<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class DepartmentPolicy
{
    public function __construct(private DataScopeService $dataScope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage') || $user->can('users.view');
    }

    public function view(User $user, Department $department): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->dataScope->canWrite($user) && $user->can('settings.manage');
    }

    public function update(User $user, Department $department): bool
    {
        return $this->dataScope->canWrite($user) && $user->can('settings.manage');
    }

    public function delete(User $user, Department $department): bool
    {
        return $this->dataScope->canWrite($user) && $user->can('settings.manage');
    }
}
