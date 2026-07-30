<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ForecastCategory;
use App\Events\OpportunityStageUpdatedEvent;
use App\Jobs\ActivateOpportunityPlaybookJob;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\PipelineStage;
use App\Models\User;
use App\Repositories\Contracts\OpportunityRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

final readonly class OpportunityCloseWorkflowService
{
    public function __construct(
        private OpportunityRepository $opportunities,
        private DataScopeService $dataScope,
        private SystemAuditService $audit,
        private OpportunityPlaybookService $playbooks,
    ) {}

    public function closeWon(User $actor, int $opportunityId, ?string $notes = null): Opportunity
    {
        return DB::transaction(function () use ($actor, $opportunityId, $notes): Opportunity {
            $opportunity = $this->opportunities->findVisibleForUpdateOrFail($actor, $opportunityId);
            $this->authorizeClosePermission($actor, $opportunity);

            $pipeline = $opportunity->pipeline;
            $wonStage = $pipeline?->stages()->where('is_won', true)->first()
                ?? PipelineStage::query()->where('pipeline_id', $opportunity->pipeline_id)->where('is_won', true)->first();

            if ($wonStage === null) {
                throw new InvalidArgumentException('Quy trình bán hàng này chưa được cấu hình Giai đoạn Thành công (Won).');
            }

            $this->playbooks->validateExitCriteria($opportunity);
            $this->playbooks->ensureCanEnterStage($actor, $wonStage->id);
            $oldStageId = $opportunity->stage_id;
            $oldStageName = $opportunity->stage?->name;

            $lastHistory = OpportunityStageHistory::query()
                ->where('opportunity_id', $opportunity->id)
                ->orderByDesc('created_at')
                ->first();

            $startTime = $lastHistory !== null ? $lastHistory->created_at : $opportunity->created_at;
            $durationSeconds = $startTime !== null ? max(0, now()->getTimestamp() - $startTime->getTimestamp()) : 0;

            $history = OpportunityStageHistory::query()->create([
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => $oldStageId,
                'to_stage_id' => $wonStage->id,
                'user_id' => $actor->getKey(),
                'notes' => $notes !== null && $notes !== '' ? $notes : 'Đóng chốt thành công hợp đồng (Won)',
                'duration_seconds' => $durationSeconds,
                'created_at' => now(),
            ]);

            $oldSnapshot = [
                'stage_id' => $oldStageId,
                'stage_name' => $oldStageName,
                'is_won' => $opportunity->is_won,
                'is_lost' => $opportunity->is_lost,
            ];

            $updatedOpportunity = $this->opportunities->update($opportunity, [
                'stage_id' => $wonStage->id,
                'is_won' => true,
                'is_lost' => false,
                'forecast_category' => ForecastCategory::Closed,
                'actual_close_date' => now()->format('Y-m-d'),
                'updated_by' => $actor->getKey(),
            ]);

            $newSnapshot = [
                'stage_id' => $wonStage->id,
                'stage_name' => $wonStage->name,
                'is_won' => true,
                'is_lost' => false,
                'history_id' => $history->id,
            ];

            $this->audit->record(
                $actor,
                $updatedOpportunity,
                'closed_won',
                'Đóng chốt thành công Cơ hội bán hàng (Won) - Giá trị: '.number_format((float) $updatedOpportunity->amount).' đ',
                $oldSnapshot,
                $newSnapshot,
            );

            OpportunityStageUpdatedEvent::dispatch(
                $updatedOpportunity,
                $oldStageId,
                $wonStage->id,
                $actor,
            );

            ActivateOpportunityPlaybookJob::dispatch($actor->id, $updatedOpportunity->id, $wonStage->id, $history->id)->afterCommit();

            return $updatedOpportunity;
        });
    }

    public function closeLost(User $actor, int $opportunityId, string $lostReason, ?string $notes = null): Opportunity
    {
        $reason = trim($lostReason);
        if ($reason === '') {
            throw new InvalidArgumentException('Bắt buộc phải nhập lý do thất bại khi đóng cơ hội.');
        }

        return DB::transaction(function () use ($actor, $opportunityId, $reason, $notes): Opportunity {
            $opportunity = $this->opportunities->findVisibleForUpdateOrFail($actor, $opportunityId);
            $this->authorizeClosePermission($actor, $opportunity);

            $pipeline = $opportunity->pipeline;
            $lostStage = $pipeline?->stages()->where('is_lost', true)->first()
                ?? PipelineStage::query()->where('pipeline_id', $opportunity->pipeline_id)->where('is_lost', true)->first();

            if ($lostStage === null) {
                throw new InvalidArgumentException('Quy trình bán hàng này chưa được cấu hình Giai đoạn Thất bại (Lost).');
            }

            $this->playbooks->validateExitCriteria($opportunity);
            $this->playbooks->ensureCanEnterStage($actor, $lostStage->id);
            $oldStageId = $opportunity->stage_id;
            $oldStageName = $opportunity->stage?->name;

            $lastHistory = OpportunityStageHistory::query()
                ->where('opportunity_id', $opportunity->id)
                ->orderByDesc('created_at')
                ->first();

            $startTime = $lastHistory !== null ? $lastHistory->created_at : $opportunity->created_at;
            $durationSeconds = $startTime !== null ? max(0, now()->getTimestamp() - $startTime->getTimestamp()) : 0;

            $historyNotes = "Lý do thất bại: {$reason}";
            if ($notes !== null && trim($notes) !== '') {
                $historyNotes .= ' - Ghi chú: '.trim($notes);
            }

            $history = OpportunityStageHistory::query()->create([
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => $oldStageId,
                'to_stage_id' => $lostStage->id,
                'user_id' => $actor->getKey(),
                'notes' => $historyNotes,
                'duration_seconds' => $durationSeconds,
                'created_at' => now(),
            ]);

            $oldSnapshot = [
                'stage_id' => $oldStageId,
                'stage_name' => $oldStageName,
                'is_won' => $opportunity->is_won,
                'is_lost' => $opportunity->is_lost,
            ];

            $updatedOpportunity = $this->opportunities->update($opportunity, [
                'stage_id' => $lostStage->id,
                'is_won' => false,
                'is_lost' => true,
                'forecast_category' => ForecastCategory::Closed,
                'lost_reason' => $reason,
                'actual_close_date' => now()->format('Y-m-d'),
                'updated_by' => $actor->getKey(),
            ]);

            $newSnapshot = [
                'stage_id' => $lostStage->id,
                'stage_name' => $lostStage->name,
                'is_won' => false,
                'is_lost' => true,
                'lost_reason' => $reason,
                'history_id' => $history->id,
            ];

            $this->audit->record(
                $actor,
                $updatedOpportunity,
                'closed_lost',
                "Đóng cơ hội bán hàng Thất bại (Lost) - Lý do: {$reason}",
                $oldSnapshot,
                $newSnapshot,
            );

            OpportunityStageUpdatedEvent::dispatch(
                $updatedOpportunity,
                $oldStageId,
                $lostStage->id,
                $actor,
            );

            ActivateOpportunityPlaybookJob::dispatch($actor->id, $updatedOpportunity->id, $lostStage->id, $history->id)->afterCommit();

            return $updatedOpportunity;
        });
    }

    public function reopen(User $actor, int $opportunityId, ?int $targetStageId = null, ?string $notes = null): Opportunity
    {
        return DB::transaction(function () use ($actor, $opportunityId, $targetStageId, $notes): Opportunity {
            $opportunity = $this->opportunities->findVisibleForUpdateOrFail($actor, $opportunityId);
            $this->authorizeClosePermission($actor, $opportunity);

            if (! $opportunity->is_won && ! $opportunity->is_lost) {
                return $opportunity; // Already open
            }

            $pipeline = $opportunity->pipeline;
            $openStage = null;

            if ($targetStageId !== null) {
                $openStage = PipelineStage::query()
                    ->where('pipeline_id', $opportunity->pipeline_id)
                    ->where('is_won', false)
                    ->where('is_lost', false)
                    ->find($targetStageId);
            }

            if ($openStage === null) {
                $openStage = $pipeline?->stages()
                    ->where('is_won', false)
                    ->where('is_lost', false)
                    ->orderBy('position', 'asc')
                    ->first();
            }

            if ($openStage === null) {
                throw new InvalidArgumentException('Quy trình bán hàng này không có Giai đoạn đang mở phù hợp để khôi phục.');
            }

            $this->playbooks->validateExitCriteria($opportunity);
            $this->playbooks->ensureCanEnterStage($actor, $openStage->id);
            $oldStageId = $opportunity->stage_id;
            $oldStageName = $opportunity->stage?->name;

            $lastHistory = OpportunityStageHistory::query()
                ->where('opportunity_id', $opportunity->id)
                ->orderByDesc('created_at')
                ->first();

            $startTime = $lastHistory !== null ? $lastHistory->created_at : $opportunity->created_at;
            $durationSeconds = $startTime !== null ? max(0, now()->getTimestamp() - $startTime->getTimestamp()) : 0;

            $history = OpportunityStageHistory::query()->create([
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => $oldStageId,
                'to_stage_id' => $openStage->id,
                'user_id' => $actor->getKey(),
                'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : 'Mở lại cơ hội bán hàng (Reopen)',
                'duration_seconds' => $durationSeconds,
                'created_at' => now(),
            ]);

            $oldSnapshot = [
                'stage_id' => $oldStageId,
                'stage_name' => $oldStageName,
                'is_won' => $opportunity->is_won,
                'is_lost' => $opportunity->is_lost,
            ];

            $updatedOpportunity = $this->opportunities->update($opportunity, [
                'stage_id' => $openStage->id,
                'is_won' => false,
                'is_lost' => false,
                'actual_close_date' => null,
                'updated_by' => $actor->getKey(),
            ]);

            $newSnapshot = [
                'stage_id' => $openStage->id,
                'stage_name' => $openStage->name,
                'is_won' => false,
                'is_lost' => false,
                'history_id' => $history->id,
            ];

            $this->audit->record(
                $actor,
                $updatedOpportunity,
                'reopened',
                "Mở lại Cơ hội bán hàng sang giai đoạn '{$openStage->name}'",
                $oldSnapshot,
                $newSnapshot,
            );

            OpportunityStageUpdatedEvent::dispatch(
                $updatedOpportunity,
                $oldStageId,
                $openStage->id,
                $actor,
            );

            ActivateOpportunityPlaybookJob::dispatch($actor->id, $updatedOpportunity->id, $openStage->id, $history->id)->afterCommit();

            return $updatedOpportunity;
        });
    }

    private function authorizeClosePermission(User $actor, Opportunity $opportunity): void
    {
        if (! $actor->can('opportunities.close')) {
            Gate::forUser($actor)->authorize('update', $opportunity);
        } else {
            if (! $this->dataScope->allows($actor, $opportunity->owner_id, $opportunity->department_id)) {
                Gate::forUser($actor)->authorize('view', $opportunity);
            }
        }
    }
}
