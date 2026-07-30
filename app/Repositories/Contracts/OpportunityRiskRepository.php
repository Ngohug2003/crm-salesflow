<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\OpportunityRiskSnapshot;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OpportunityRiskRepository
{
    public function latestForOpportunity(int $opportunityId): ?OpportunityRiskSnapshot;

    /**
     * @param  array{opportunity_id: int, rule_version: string, evaluation_bucket: \DateTimeInterface, score: int, level: string, summary: string|null, is_current: bool, evaluated_at: \DateTimeInterface}  $data
     * @param  list<array{code: string, title: string, points: int, recommended_action: string, details: array<string, mixed>}>  $factors
     */
    public function createSnapshotIfMissing(array $data, array $factors): OpportunityRiskSnapshot;

    /** @return LengthAwarePaginator<int, OpportunityRiskSnapshot> */
    public function paginateVisible(
        User $actor,
        ?string $level,
        ?int $ownerId,
        ?int $pipelineId,
        int $perPage,
    ): LengthAwarePaginator;

    public function findVisibleOrFail(User $actor, int $snapshotId): OpportunityRiskSnapshot;

    /** @return list<int> */
    public function openOpportunityIds(int $chunkSize): array;
}
