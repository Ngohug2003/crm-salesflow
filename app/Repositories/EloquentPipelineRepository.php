<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\PipelineFilterData;
use App\Models\Pipeline;
use App\Models\User;
use App\Repositories\Contracts\PipelineRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentPipelineRepository implements PipelineRepository
{
    /** @var list<string> */
    private const array ALLOWED_SORT_FIELDS = ['name', 'code', 'created_at', 'is_default', 'is_active'];

    public function __construct(private DataScopeService $dataScope) {}

    /** @return LengthAwarePaginator<int, Pipeline> */
    public function paginateVisible(User $actor, PipelineFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->visibleQuery($actor);

        if ($filters->search !== null && trim($filters->search) !== '') {
            $search = trim($filters->search);
            $query->where(static function (Builder $q) use ($search): void {
                $q->whereLike('name', $search, caseSensitive: false)
                    ->orWhereLike('code', $search, caseSensitive: false)
                    ->orWhereLike('description', $search, caseSensitive: false);
            });
        }

        if ($filters->isActive !== null) {
            $query->where('is_active', $filters->isActive);
        }

        if ($filters->isDefault !== null) {
            $query->where('is_default', $filters->isDefault);
        }

        if ($filters->ownerId !== null) {
            $query->where('owner_id', $filters->ownerId);
        }

        if ($filters->departmentId !== null) {
            $query->where('department_id', $filters->departmentId);
        }

        $sortField = in_array($filters->sortBy, self::ALLOWED_SORT_FIELDS, true) ? $filters->sortBy : 'created_at';
        $sortDirection = strtolower($filters->sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->with(['owner', 'department', 'stages'])
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);
    }

    public function findVisibleOrFail(User $actor, int $id): Pipeline
    {
        /** @var Pipeline */
        return $this->visibleQuery($actor)
            ->with(['owner', 'department', 'createdBy', 'updatedBy', 'stages'])
            ->findOrFail($id);
    }

    public function findVisibleForUpdateOrFail(User $actor, int $id): Pipeline
    {
        /** @var Pipeline */
        return $this->visibleQuery($actor)
            ->lockForUpdate()
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Pipeline
    {
        return Pipeline::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Pipeline $pipeline, array $data): Pipeline
    {
        $pipeline->update($data);

        return $pipeline->fresh() ?? $pipeline;
    }

    public function softDelete(Pipeline $pipeline): Pipeline
    {
        $pipeline->delete();

        return $pipeline;
    }

    public function resetDefaultExcept(?int $excludePipelineId = null): void
    {
        $query = Pipeline::query();
        if ($excludePipelineId !== null) {
            $query->where('id', '!=', $excludePipelineId);
        }
        $query->update(['is_default' => false]);
    }

    /** @return Builder<Pipeline> */
    private function visibleQuery(User $actor): Builder
    {
        return $this->dataScope->apply(
            Pipeline::query(),
            $actor,
            'owner_id',
            'department_id',
        );
    }
}
