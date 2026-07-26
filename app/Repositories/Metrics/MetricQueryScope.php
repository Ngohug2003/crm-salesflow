<?php

declare(strict_types=1);

namespace App\Repositories\Metrics;

use App\Data\ReportFilterData;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Database\Eloquent\Builder;

final readonly class MetricQueryScope
{
    public function __construct(private DataScopeService $dataScope) {}

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    public function apply(
        Builder $query,
        User $actor,
        ReportFilterData $filters,
        string $ownerColumn = 'owner_id',
        string $departmentColumn = 'department_id',
    ): void {
        $model = $query->getModel();

        if ($filters->userId !== null) {
            $query->where($model->qualifyColumn($ownerColumn), $filters->userId);
        }

        if ($filters->departmentId !== null) {
            $query->where($model->qualifyColumn($departmentColumn), $filters->departmentId);
        }

        $this->dataScope->apply($query, $actor, $ownerColumn, $departmentColumn);
    }
}
