<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Opportunity;
use App\Models\OpportunityRiskSnapshot;
use App\Models\User;
use App\Repositories\Contracts\OpportunityRiskRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class EloquentOpportunityRiskRepository implements OpportunityRiskRepository
{
    public function __construct(private DataScopeService $dataScope) {}

    public function latestForOpportunity(int $opportunityId): ?OpportunityRiskSnapshot
    {
        return OpportunityRiskSnapshot::query()
            ->with(['factors', 'opportunity.latestRiskAcknowledgement.user'])
            ->where('opportunity_id', $opportunityId)
            ->where('is_current', true)
            ->latest('evaluated_at')
            ->first();
    }

    public function createSnapshotIfMissing(array $data, array $factors): OpportunityRiskSnapshot
    {
        return DB::transaction(function () use ($data, $factors): OpportunityRiskSnapshot {
            $existing = OpportunityRiskSnapshot::query()
                ->with(['factors', 'opportunity.latestRiskAcknowledgement.user'])
                ->where('opportunity_id', $data['opportunity_id'])
                ->where('rule_version', $data['rule_version'])
                ->where('evaluation_bucket', $data['evaluation_bucket'])
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            OpportunityRiskSnapshot::query()
                ->where('opportunity_id', $data['opportunity_id'])
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $snapshot = OpportunityRiskSnapshot::query()->create($data);
            $snapshot->factors()->createMany($factors);

            return $snapshot->load(['factors', 'opportunity.latestRiskAcknowledgement.user']);
        });
    }

    public function paginateVisible(User $actor, ?string $level, ?int $ownerId, ?int $pipelineId, int $perPage): LengthAwarePaginator
    {
        return OpportunityRiskSnapshot::query()
            ->with(['opportunity.stage', 'opportunity.owner', 'factors', 'opportunity.latestRiskAcknowledgement.user'])
            ->where('is_current', true)
            ->when($level !== null, fn ($query) => $query->where('risk_level', $level))
            ->whereHas('opportunity', function ($query) use ($actor, $ownerId, $pipelineId): void {
                $query->where('is_won', false)->where('is_lost', false);
                $this->dataScope->apply($query, $actor, 'owner_id', 'department_id');
                if ($ownerId !== null) {
                    $query->where('owner_id', $ownerId);
                }
                if ($pipelineId !== null) {
                    $query->where('pipeline_id', $pipelineId);
                }
            })
            ->orderByDesc('risk_score')
            ->orderByDesc('evaluated_at')
            ->paginate($perPage);
    }

    public function findVisibleOrFail(User $actor, int $snapshotId): OpportunityRiskSnapshot
    {
        /** @var OpportunityRiskSnapshot $snapshot */
        $snapshot = OpportunityRiskSnapshot::query()
            ->with(['opportunity', 'factors', 'opportunity.latestRiskAcknowledgement.user'])
            ->findOrFail($snapshotId);

        if (! $this->dataScope->allows($actor, $snapshot->opportunity->owner_id, $snapshot->opportunity->department_id)) {
            abort(403);
        }

        return $snapshot;
    }

    public function openOpportunityIds(int $chunkSize): array
    {
        return Opportunity::query()
            ->where('is_won', false)
            ->where('is_lost', false)
            ->orderBy('id')
            ->limit($chunkSize)
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();
    }
}
