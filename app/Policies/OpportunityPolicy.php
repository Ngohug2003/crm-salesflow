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
        return $user->hasAnyPermission(['opportunities.view', 'opportunities.view-all']);
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        if (! $user->hasAnyPermission(['opportunities.view', 'opportunities.view-all'])) {
            return false;
        }

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    public function create(User $user): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        return $user->hasPermissionTo('opportunities.create');
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        if (! $user->hasPermissionTo('opportunities.update')) {
            return false;
        }

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        if (! $user->hasPermissionTo('opportunities.delete')) {
            return false;
        }

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }
}
