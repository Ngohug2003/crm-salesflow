<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\OpportunityFilterData;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OpportunityRepository
{
    /** @return LengthAwarePaginator<int, Opportunity> */
    public function paginateVisible(User $actor, OpportunityFilterData $filters, int $perPage = 15): LengthAwarePaginator;

    public function findVisibleOrFail(User $actor, int $id): Opportunity;

    public function findVisibleForUpdateOrFail(User $actor, int $id): Opportunity;

    /** @param array<string, mixed> $data */
    public function create(array $data): Opportunity;

    /** @param array<string, mixed> $data */
    public function update(Opportunity $opportunity, array $data): Opportunity;

    public function softDelete(Opportunity $opportunity): Opportunity;

    /** @return array{total_count: int, total_amount: float, total_weighted_value: float} */
    public function summarizeVisible(User $actor, OpportunityFilterData $filters): array;
}
