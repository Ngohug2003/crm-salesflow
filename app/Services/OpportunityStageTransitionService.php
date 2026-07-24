<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\OpportunityStageUpdatedEvent;
use App\Exceptions\StaleOpportunityException;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\PipelineStage;
use App\Models\User;
use App\Repositories\Contracts\OpportunityRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class OpportunityStageTransitionService
{
    public function __construct(
        private OpportunityRepository $opportunities,
        private DataScopeService $dataScope,
        private SystemAuditService $audit,
    ) {}

    public function transitionStage(
        User $actor,
        int $opportunityId,
        int $targetStageId,
        ?int $expectedCurrentStageId = null,
        ?string $notes = null,
    ): Opportunity {
        return DB::transaction(function () use ($actor, $opportunityId, $targetStageId, $expectedCurrentStageId, $notes): Opportunity {
            $opportunity = $this->opportunities->findVisibleForUpdateOrFail($actor, $opportunityId);

            if (! $actor->hasPermissionTo('opportunities.change-stage')) {
                Gate::forUser($actor)->authorize('update', $opportunity);
            } else {
                if (! $this->dataScope->allows($actor, $opportunity->owner_id, $opportunity->department_id)) {
                    Gate::forUser($actor)->authorize('view', $opportunity);
                }
            }

            if ($expectedCurrentStageId !== null && $opportunity->stage_id !== $expectedCurrentStageId) {
                throw new StaleOpportunityException('Giai đoạn hiện tại của Cơ hội bán hàng đã được cập nhật bởi thành viên khác. Vui lòng tải lại trang.');
            }

            if ($opportunity->stage_id === $targetStageId) {
                return $opportunity;
            }

            $targetStage = PipelineStage::query()
                ->where('pipeline_id', $opportunity->pipeline_id)
                ->findOrFail($targetStageId);

            $oldStageId = $opportunity->stage_id;
            $oldStage = $opportunity->stage;

            $lastHistory = OpportunityStageHistory::query()
                ->where('opportunity_id', $opportunity->id)
                ->orderByDesc('created_at')
                ->first();

            $startTime = $lastHistory !== null ? $lastHistory->created_at : $opportunity->created_at;
            $durationSeconds = $startTime !== null ? max(0, now()->getTimestamp() - $startTime->getTimestamp()) : 0;

            $history = OpportunityStageHistory::query()->create([
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => $oldStageId,
                'to_stage_id' => $targetStage->id,
                'user_id' => $actor->getKey(),
                'notes' => $notes,
                'duration_seconds' => $durationSeconds,
                'created_at' => now(),
            ]);

            $isWon = (bool) $targetStage->is_won;
            $isLost = (bool) $targetStage->is_lost;
            $actualCloseDate = ($isWon || $isLost) ? now()->format('Y-m-d') : null;

            $oldSnapshot = [
                'stage_id' => $oldStageId,
                'stage_name' => $oldStage?->name,
                'is_won' => $opportunity->is_won,
                'is_lost' => $opportunity->is_lost,
            ];

            $updatedOpportunity = $this->opportunities->update($opportunity, [
                'stage_id' => $targetStage->id,
                'is_won' => $isWon,
                'is_lost' => $isLost,
                'actual_close_date' => $actualCloseDate,
                'updated_by' => $actor->getKey(),
            ]);

            $newSnapshot = [
                'stage_id' => $targetStage->id,
                'stage_name' => $targetStage->name,
                'is_won' => $isWon,
                'is_lost' => $isLost,
                'history_id' => $history->id,
                'duration_seconds' => $durationSeconds,
            ];

            $this->audit->record(
                $actor,
                $updatedOpportunity,
                'stage_changed',
                "Chuyển giai đoạn cơ hội sang '{$targetStage->name}' ({$targetStage->probability}%)",
                $oldSnapshot,
                $newSnapshot,
            );

            OpportunityStageUpdatedEvent::dispatch(
                $updatedOpportunity,
                $oldStageId,
                $targetStage->id,
                $actor,
            );

            return $updatedOpportunity;
        });
    }
}
