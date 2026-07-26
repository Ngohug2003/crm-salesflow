<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class OpportunityPolicy
{
    public function __construct(private DataScopeService $dataScope) {}

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'opportunities.view') || $this->hasPermission($user, 'opportunities.view-all');
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        if (! $this->hasPermission($user, 'opportunities.view') && ! $this->hasPermission($user, 'opportunities.view-all')) {
            return false;
        }

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    public function create(User $user): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        return $this->hasPermission($user, 'opportunities.create');
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        if (! $this->hasPermission($user, 'opportunities.update')) {
            return false;
        }

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        if (! $this->hasPermission($user, 'opportunities.delete')) {
            return false;
        }

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return false;
        }
    }
}
