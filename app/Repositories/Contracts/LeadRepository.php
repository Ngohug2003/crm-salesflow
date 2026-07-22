<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\LeadFilterData;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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

    /** @return Builder<Lead> */
    public function trashedVisibleTo(User $actor): Builder;

    /** @return LengthAwarePaginator<int, Lead> */
    public function paginateTrashedVisibleTo(User $actor, string $search, int $perPage = 15): LengthAwarePaginator;

    public function findTrashedVisibleOrFail(User $actor, int $leadId): Lead;

    public function findTrashedVisibleForUpdateOrFail(User $actor, int $leadId): Lead;

    /** @param list<string> $phones
     * @return Collection<int, Lead>
     */
    public function duplicateCandidates(
        User $actor,
        ?string $email,
        array $phones,
        ?int $excludeLeadId = null,
    ): Collection;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Lead;

    /** @param array<string, mixed> $attributes */
    public function update(Lead $lead, array $attributes): Lead;

    /** @param list<int> $tagIds */
    public function syncTags(Lead $lead, array $tagIds): Lead;

    public function softDelete(Lead $lead): Lead;

    public function restore(Lead $lead): Lead;
}
