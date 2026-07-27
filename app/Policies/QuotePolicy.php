<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

final readonly class QuotePolicy
{
    public function __construct(private DataScopeService $dataScope) {}

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'opportunities.view') || $this->hasPermission($user, 'opportunities.view-all');
    }

    public function view(User $user, Quote $quote): bool
    {
        if (! $this->hasPermission($user, 'opportunities.view') && ! $this->hasPermission($user, 'opportunities.view-all')) {
            return false;
        }

        $opportunity = $quote->opportunity;

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    public function create(User $user): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        return $this->hasPermission($user, 'opportunities.create') || $this->hasPermission($user, 'opportunities.update');
    }

    public function update(User $user, Quote $quote): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        if (! $this->hasPermission($user, 'opportunities.update')) {
            return false;
        }

        $opportunity = $quote->opportunity;

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    public function delete(User $user, Quote $quote): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        if (! $this->hasPermission($user, 'opportunities.delete') && ! $this->hasPermission($user, 'opportunities.update')) {
            return false;
        }

        $opportunity = $quote->opportunity;

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
