<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\OpportunityStageUpdatedEvent;
use App\Exceptions\StageRequirementsUnfulfilledException;
use App\Exceptions\StaleOpportunityException;
use App\Jobs\ActivateOpportunityPlaybookJob;
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
        private OpportunityPlaybookService $playbooks,
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

            if (! $actor->can('opportunities.change-stage')) {
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

            $this->playbooks->validateExitCriteria($opportunity);
            $this->playbooks->ensureCanEnterStage($actor, $targetStage->id);
            $this->validateStageRequirements($opportunity, $targetStage, $notes);

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

            ActivateOpportunityPlaybookJob::dispatch(
                $actor->id,
                $updatedOpportunity->id,
                $targetStage->id,
                $history->id,
            )->afterCommit();

            return $updatedOpportunity;
        });
    }

    private function validateStageRequirements(Opportunity $opportunity, PipelineStage $targetStage, ?string $notes = null): void
    {
        $probability = (int) $targetStage->probability;

        // Rule 1: Closed Lost validation
        if ((bool) $targetStage->is_lost && trim((string) $notes) === '' && trim((string) $opportunity->lost_reason) === '') {
            throw new StageRequirementsUnfulfilledException(
                "Không thể chuyển sang trạng thái Thất bại '{$targetStage->name}' nếu chưa nhập Lý do thất bại."
            );
        }

        // Rule 2: Quote/Proposal stage (probability >= 20%) requires amount > 0
        if ($probability >= 20 && (float) $opportunity->amount <= 0) {
            throw new StageRequirementsUnfulfilledException(
                "Không thể chuyển sang giai đoạn '{$targetStage->name}' ({$probability}%). Cơ hội bán hàng phải có Giá trị dự kiến (Doanh thu) lớn hơn 0 VNĐ."
            );
        }

        // Rule 3: Negotiation stage (probability >= 50%) requires expected_close_date
        if ($probability >= 50 && $opportunity->expected_close_date === null) {
            throw new StageRequirementsUnfulfilledException(
                "Không thể chuyển sang giai đoạn '{$targetStage->name}' ({$probability}%). Vui lòng cập nhật Ngày đóng dự kiến (Expected Close Date)."
            );
        }

        // Rule 4: Closing/Won stage (probability >= 70% or is_won) requires company_id or contact_id
        if (($probability >= 70 || (bool) $targetStage->is_won) && $opportunity->company_id === null && $opportunity->contact_id === null) {
            throw new StageRequirementsUnfulfilledException(
                "Không thể chuyển sang giai đoạn '{$targetStage->name}' ({$probability}%). Cơ hội bán hàng phải được liên kết với ít nhất 1 Doanh nghiệp hoặc Người liên hệ đại diện."
            );
        }
    }
}
