<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\OpportunityFilterData;
use App\Models\Opportunity;
use App\Models\User;
use App\Repositories\Contracts\OpportunityRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentOpportunityRepository implements OpportunityRepository
{
    /** @var list<string> */
    private const array ALLOWED_SORT_FIELDS = ['title', 'code', 'amount', 'expected_close_date', 'created_at'];

    public function __construct(private DataScopeService $dataScope) {}

    /** @return LengthAwarePaginator<int, Opportunity> */
    public function paginateVisible(User $actor, OpportunityFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildFilterQuery($actor, $filters);

        $sortField = in_array($filters->sortBy, self::ALLOWED_SORT_FIELDS, true) ? $filters->sortBy : 'created_at';
        $sortDirection = strtolower($filters->sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->with(['pipeline', 'stage', 'company', 'contact', 'owner'])
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);
    }

    public function findVisibleOrFail(User $actor, int $id): Opportunity
    {
        /** @var Opportunity */
        return $this->visibleQuery($actor)
            ->with(['pipeline', 'stage', 'company', 'contact', 'lead', 'owner', 'department', 'createdBy', 'updatedBy'])
            ->findOrFail($id);
    }

    public function findVisibleForUpdateOrFail(User $actor, int $id): Opportunity
    {
        /** @var Opportunity */
        return $this->visibleQuery($actor)
            ->lockForUpdate()
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Opportunity
    {
        return Opportunity::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Opportunity $opportunity, array $data): Opportunity
    {
        $opportunity->update($data);

        return $opportunity->fresh() ?? $opportunity;
    }

    public function softDelete(Opportunity $opportunity): Opportunity
    {
        $opportunity->delete();

        return $opportunity;
    }

    /** @return array{total_count: int, total_amount: float, total_weighted_value: float} */
    public function summarizeVisible(User $actor, OpportunityFilterData $filters): array
    {
        $opportunities = $this->buildFilterQuery($actor, $filters)->with('stage')->get();

        $totalCount = $opportunities->count();
        $totalAmount = 0.0;
        $totalWeightedValue = 0.0;

        foreach ($opportunities as $opp) {
            $totalAmount += (float) $opp->amount;
            $totalWeightedValue += $opp->weighted_value;
        }

        return [
            'total_count' => $totalCount,
            'total_amount' => round($totalAmount, 2),
            'total_weighted_value' => round($totalWeightedValue, 2),
        ];
    }

    /** @return Builder<Opportunity> */
    private function buildFilterQuery(User $actor, OpportunityFilterData $filters): Builder
    {
        $query = $this->visibleQuery($actor);

        if ($filters->search !== null && trim($filters->search) !== '') {
            $search = trim($filters->search);
            $query->where(static function (Builder $q) use ($search): void {
                $q->whereLike('title', $search, caseSensitive: false)
                    ->orWhereLike('code', $search, caseSensitive: false)
                    ->orWhereLike('notes', $search, caseSensitive: false);
            });
        }

        if ($filters->pipelineId !== null) {
            $query->where('pipeline_id', $filters->pipelineId);
        }

        if ($filters->stageId !== null) {
            $query->where('stage_id', $filters->stageId);
        }

        if ($filters->companyId !== null) {
            $query->where('company_id', $filters->companyId);
        }

        if ($filters->contactId !== null) {
            $query->where('contact_id', $filters->contactId);
        }

        if ($filters->ownerId !== null) {
            $query->where('owner_id', $filters->ownerId);
        }

        if ($filters->departmentId !== null) {
            $query->where('department_id', $filters->departmentId);
        }

        if ($filters->status === 'won') {
            $query->where('is_won', true);
        } elseif ($filters->status === 'lost') {
            $query->where('is_lost', true);
        } elseif ($filters->status === 'open') {
            $query->where('is_won', false)->where('is_lost', false);
        }

        if ($filters->forecastCategory !== null && $filters->forecastCategory !== '') {
            $query->where('forecast_category', $filters->forecastCategory);
        }

        if ($filters->expectedCloseFrom !== null && $filters->expectedCloseFrom !== '') {
            $query->whereDate('expected_close_date', '>=', $filters->expectedCloseFrom);
        }

        if ($filters->expectedCloseTo !== null && $filters->expectedCloseTo !== '') {
            $query->whereDate('expected_close_date', '<=', $filters->expectedCloseTo);
        }

        return $query;
    }

    /** @return Builder<Opportunity> */
    private function visibleQuery(User $actor): Builder
    {
        return $this->dataScope->apply(
            Opportunity::query(),
            $actor,
            'owner_id',
            'department_id',
        );
    }
}
