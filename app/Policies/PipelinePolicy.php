<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pipeline;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class PipelinePolicy
{
    public function __construct(private DataScopeService $dataScope) {}

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['pipelines.view', 'pipelines.manage']);
    }

    public function view(User $user, Pipeline $pipeline): bool
    {
        if (! $user->hasAnyPermission(['pipelines.view', 'pipelines.manage'])) {
            return false;
        }

        return $this->dataScope->allows($user, $pipeline->owner_id, $pipeline->department_id);
    }

    public function create(User $user): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        return $user->hasAnyPermission(['pipelines.create', 'pipelines.manage']);
    }

    public function update(User $user, Pipeline $pipeline): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        if (! $user->hasAnyPermission(['pipelines.update', 'pipelines.manage'])) {
            return false;
        }

        return $this->dataScope->allows($user, $pipeline->owner_id, $pipeline->department_id);
    }

    public function delete(User $user, Pipeline $pipeline): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        if (! $user->hasAnyPermission(['pipelines.delete', 'pipelines.manage'])) {
            return false;
        }

        return $this->dataScope->allows($user, $pipeline->owner_id, $pipeline->department_id);
    }
}
