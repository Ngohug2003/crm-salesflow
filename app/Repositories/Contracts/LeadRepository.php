<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\LeadFilterData;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface LeadRepository
{
    /** @return Builder<Lead> */
    public function visibleTo(User $actor): Builder;

    /** @return Builder<Lead> */
    public function filteredVisibleTo(User $actor, LeadFilterData $filters): Builder;

    /** @return LengthAwarePaginator<int, Lead> */
    public function paginateVisibleTo(
        User $actor,
        LeadFilterData $filters,
        int $perPage = 15,
    ): LengthAwarePaginator;

    public function findVisibleOrFail(User $actor, int $leadId): Lead;

    public function findVisibleForUpdateOrFail(User $actor, int $leadId): Lead;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Lead;

    /** @param array<string, mixed> $attributes */
    public function update(Lead $lead, array $attributes): Lead;

    /** @param list<int> $tagIds */
    public function syncTags(Lead $lead, array $tagIds): Lead;
}
