<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\DataScope;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Support\Collection;

final readonly class ReportFilterOptionsService
{
    public function __construct(private DataScopeService $dataScope) {}

    /** @return Collection<int, Department> */
    public function departments(User $actor): Collection
    {
        $query = Department::query()->orderBy('name');

        if (in_array($this->dataScope->resolve($actor), [DataScope::Department, DataScope::Owned], true)) {
            $query->whereKey($actor->department_id ?? 0);
        }

        return $query->get(['id', 'name']);
    }

    /** @return Collection<int, User> */
    public function users(User $actor, ?int $departmentId = null): Collection
    {
        $query = User::query()->orderBy('name');
        $scope = $this->dataScope->resolve($actor);

        if ($scope === DataScope::Department) {
            $query->where('department_id', $actor->department_id ?? 0);
        } elseif ($scope === DataScope::Owned) {
            $query->whereKey($actor->getKey());
        }

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }

        return $query->get(['id', 'name', 'department_id']);
    }

    /** @return Collection<int, Pipeline> */
    public function pipelines(User $actor): Collection
    {
        $query = Pipeline::query()->active()->orderBy('name');
        $scope = $this->dataScope->resolve($actor);

        if (in_array($scope, [DataScope::Department, DataScope::Owned], true)) {
            $visibleOpportunityPipelines = Opportunity::query()->select('pipeline_id');
            $this->dataScope->apply($visibleOpportunityPipelines, $actor);
            $query->whereIn('id', $visibleOpportunityPipelines);
        }

        return $query->get(['id', 'name', 'owner_id', 'department_id']);
    }
}
