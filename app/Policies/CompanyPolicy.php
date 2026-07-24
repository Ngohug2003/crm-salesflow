<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class CompanyPolicy
{
    public function __construct(private DataScopeService $dataScope) {}

    public function viewAny(User $actor): bool
    {
        return $actor->can('companies.view');
    }

    public function view(User $actor, Company $company): bool
    {
        return $actor->can('companies.view') && $this->withinScope($actor, $company);
    }

    public function create(User $actor): bool
    {
        return $this->dataScope->canWrite($actor) && $actor->can('companies.create');
    }

    public function update(User $actor, Company $company): bool
    {
        return $this->canMutate($actor, $company, 'companies.update');
    }

    public function delete(User $actor, Company $company): bool
    {
        return $this->canMutate($actor, $company, 'companies.delete');
    }

    public function restore(User $actor, Company $company): bool
    {
        return $this->canMutate($actor, $company, 'companies.delete');
    }

    public function forceDelete(User $actor, Company $company): bool
    {
        return false;
    }

    private function canMutate(User $actor, Company $company, string $permission): bool
    {
        return $this->dataScope->canWrite($actor)
            && $actor->can($permission)
            && $this->withinScope($actor, $company);
    }

    private function withinScope(User $actor, Company $company): bool
    {
        return $this->dataScope->allows(
            $actor,
            $company->owner_id,
            $company->department_id,
        );
    }
}
