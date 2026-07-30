<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlaybookRepeatPolicy;
use App\Enums\SalesPlaybookStatus;
use App\Enums\SalesPlaybookStepType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Exceptions\StageRequirementsUnfulfilledException;
use App\Models\Opportunity;
use App\Models\OpportunityPlaybookRun;
use App\Models\OpportunityPlaybookStepRun;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\SalesPlaybookRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class OpportunityPlaybookService
{
    public function __construct(
        private SalesPlaybookRepository $playbooks,
        private TaskManagementService $tasks,
        private BusinessDayCalculator $businessDays,
        private SystemAuditService $audit,
    ) {}

    public function ensureCanEnterStage(User $actor, int $stageId): void
    {
        $assignment = $this->playbooks->assignmentForStage($stageId);
        if ($assignment === null || $assignment->playbook->status !== SalesPlaybookStatus::Published) {
            return;
        }

        $createsTask = $assignment->playbook->steps->contains(
            fn ($step): bool => in_array($step->type, [SalesPlaybookStepType::Task, SalesPlaybookStepType::Reminder], true),
        );

        if ($createsTask) {
            Gate::forUser($actor)->authorize('create', Task::class);
        }
    }

    public function validateExitCriteria(Opportunity $opportunity): void
    {
        $run = $this->playbooks->latestRunForOpportunityStage($opportunity->id, $opportunity->stage_id);
        if ($run === null) {
            return;
        }

        $this->synchronizeDerivedSteps($run, $opportunity);
        $blocking = $run->fresh('steps')?->steps
            ->filter(fn (OpportunityPlaybookStepRun $step): bool => $step->blocks_stage_exit && $step->status !== 'completed')
            ->pluck('title')
            ->values()
            ->all() ?? [];

        if ($blocking !== []) {
            throw new StageRequirementsUnfulfilledException(
                'Chưa thể rời giai đoạn hiện tại. Vui lòng hoàn tất: '.implode('; ', $blocking).'.',
            );
        }
    }

    public function activateForStage(
        User $actor,
        Opportunity $opportunity,
        int $stageId,
        int $stageEntryId,
        bool $manual = false,
        ?string $idempotencyKey = null,
    ): ?OpportunityPlaybookRun {
        Gate::forUser($actor)->authorize('sales-playbook.view');
        $assignment = $this->playbooks->assignmentForStage($stageId);
        if ($assignment === null || $assignment->playbook->status !== SalesPlaybookStatus::Published) {
            return null;
        }

        $playbook = $assignment->playbook;
        if ($playbook->repeat_policy === PlaybookRepeatPolicy::Manual && ! $manual) {
            return null;
        }

        $this->ensureCanEnterStage($actor, $stageId);
        $key = $idempotencyKey ?? ($playbook->repeat_policy === PlaybookRepeatPolicy::Once
            ? "opportunity:{$opportunity->id}:playbook:{$playbook->id}:once"
            : "opportunity:{$opportunity->id}:playbook:{$playbook->id}:entry:{$stageEntryId}");

        return DB::transaction(function () use ($actor, $opportunity, $stageId, $playbook, $key): OpportunityPlaybookRun {
            $stepRuns = [];
            foreach ($playbook->steps as $step) {
                $type = $step->type;
                $derivedComplete = in_array($type, [SalesPlaybookStepType::Guidance, SalesPlaybookStepType::Document], true)
                    || ($type === SalesPlaybookStepType::RequiredField && $this->opportunityFieldIsPresent($opportunity, $step->configuration['field_name'] ?? ''));
                $dueAt = $step->due_business_days !== null
                    ? $this->businessDays->addBusinessDays(now(), $step->due_business_days)
                    : null;

                $stepRuns[] = [
                    'sales_playbook_step_id' => $step->id,
                    'position' => $step->position,
                    'type' => $type->value,
                    'title' => $step->title,
                    'instructions' => $step->instructions,
                    'configuration' => $step->configuration,
                    'is_required' => $step->is_required,
                    'blocks_stage_exit' => $step->blocks_stage_exit,
                    'status' => $derivedComplete ? 'completed' : 'pending',
                    'due_at' => $dueAt,
                    'completed_at' => $derivedComplete ? now() : null,
                ];
            }

            $run = $this->playbooks->createRunIfMissing([
                'opportunity_id' => $opportunity->id,
                'sales_playbook_id' => $playbook->id,
                'pipeline_stage_id' => $stageId,
                'started_by' => $actor->id,
                'idempotency_key' => $key,
                'status' => 'active',
                'repeat_policy' => $playbook->repeat_policy->value,
                'playbook_snapshot' => [
                    'code' => $playbook->code,
                    'name' => $playbook->name,
                    'version' => $playbook->version,
                    'description' => $playbook->description,
                ],
                'started_at' => now(),
            ], $stepRuns);

            foreach ($run->steps as $stepRun) {
                if ($stepRun->task_id !== null || ! in_array($stepRun->type, [SalesPlaybookStepType::Task, SalesPlaybookStepType::Reminder], true)) {
                    continue;
                }

                $task = $this->tasks->create($actor, [
                    'title' => $stepRun->title,
                    'description' => $stepRun->instructions,
                    'status' => TaskStatus::Todo->value,
                    'priority' => TaskPriority::Medium->value,
                    'due_date' => $stepRun->due_at,
                    'reminder_at' => $stepRun->type === SalesPlaybookStepType::Reminder ? $stepRun->due_at : null,
                    'assigned_to' => $opportunity->owner_id ?? $actor->id,
                    'subject_type' => Opportunity::class,
                    'subject_id' => $opportunity->id,
                ]);
                $stepRun->update(['task_id' => $task->id]);
            }

            $this->audit->record(
                $actor,
                $opportunity,
                'sales_playbook.activated',
                "Kích hoạt playbook '{$playbook->name}' v{$playbook->version}",
                null,
                ['playbook_run_id' => $run->id, 'pipeline_stage_id' => $stageId],
            );

            return $run->fresh(['steps.task', 'playbook', 'stage']) ?? $run;
        });
    }

    public function activeRun(User $actor, Opportunity $opportunity): ?OpportunityPlaybookRun
    {
        Gate::forUser($actor)->authorize('view', $opportunity);
        Gate::forUser($actor)->authorize('sales-playbook.view');
        $run = $this->playbooks->latestRunForOpportunityStage($opportunity->id, $opportunity->stage_id);
        if ($run !== null) {
            $this->synchronizeDerivedSteps($run, $opportunity);
        }

        return $run?->fresh(['steps.task', 'playbook', 'stage']);
    }

    public function restart(User $actor, Opportunity $opportunity): OpportunityPlaybookRun
    {
        Gate::forUser($actor)->authorize('update', $opportunity);
        Gate::forUser($actor)->authorize('sales-playbook.view');

        return DB::transaction(function () use ($actor, $opportunity): OpportunityPlaybookRun {
            $lockedOpportunity = Opportunity::query()->whereKey($opportunity->id)->lockForUpdate()->firstOrFail();
            $latestRunId = (int) (OpportunityPlaybookRun::query()
                ->where('opportunity_id', $lockedOpportunity->id)
                ->where('pipeline_stage_id', $lockedOpportunity->stage_id)
                ->latest('id')
                ->value('id') ?? 0);

            OpportunityPlaybookRun::query()
                ->where('opportunity_id', $lockedOpportunity->id)
                ->where('pipeline_stage_id', $lockedOpportunity->stage_id)
                ->where('status', 'active')
                ->update(['status' => 'superseded']);

            $sequence = $latestRunId + 1;
            $run = $this->activateForStage(
                $actor,
                $lockedOpportunity,
                $lockedOpportunity->stage_id,
                $sequence,
                true,
                "opportunity:{$lockedOpportunity->id}:stage:{$lockedOpportunity->stage_id}:restart:{$sequence}",
            );

            if ($run === null) {
                throw ValidationException::withMessages([
                    'playbook' => 'Giai đoạn hiện tại chưa có Playbook đã phát hành để chạy lại.',
                ]);
            }

            $this->audit->record(
                $actor,
                $lockedOpportunity,
                'sales_playbook.restarted',
                "Chạy lại playbook '{$run->playbook_snapshot['name']}'",
                ['previous_run_id' => $latestRunId > 0 ? $latestRunId : null],
                ['playbook_run_id' => $run->id],
            );

            return $run;
        });
    }

    public function canStart(User $actor, Opportunity $opportunity): bool
    {
        Gate::forUser($actor)->authorize('view', $opportunity);
        Gate::forUser($actor)->authorize('sales-playbook.view');
        $assignment = $this->playbooks->assignmentForStage($opportunity->stage_id);

        return $assignment !== null
            && $assignment->playbook->status === SalesPlaybookStatus::Published;
    }

    public function awaitingAutomaticActivation(User $actor, Opportunity $opportunity): bool
    {
        Gate::forUser($actor)->authorize('view', $opportunity);
        Gate::forUser($actor)->authorize('sales-playbook.view');

        $assignment = $this->playbooks->assignmentForStage($opportunity->stage_id);

        return $assignment !== null
            && $assignment->playbook->status === SalesPlaybookStatus::Published
            && $assignment->playbook->repeat_policy !== PlaybookRepeatPolicy::Manual;
    }

    public function completeStep(User $actor, int $stepRunId, ?string $response = null): OpportunityPlaybookRun
    {
        $step = OpportunityPlaybookStepRun::query()->with(['run.opportunity', 'task'])->findOrFail($stepRunId);
        $opportunity = $step->run->opportunity;
        Gate::forUser($actor)->authorize('update', $opportunity);
        Gate::forUser($actor)->authorize('sales-playbook.view');

        if (in_array($step->type, [SalesPlaybookStepType::RequiredField, SalesPlaybookStepType::Guidance, SalesPlaybookStepType::Document], true)) {
            throw ValidationException::withMessages(['step' => 'Bước này được hệ thống tự xác nhận và không thể hoàn tất thủ công.']);
        }
        if ($step->type === SalesPlaybookStepType::Question && trim((string) $response) === '') {
            throw ValidationException::withMessages(['response' => 'Vui lòng nhập câu trả lời trước khi hoàn tất bước qualification.']);
        }

        return DB::transaction(function () use ($actor, $step, $opportunity, $response): OpportunityPlaybookRun {
            if ($step->task !== null) {
                Gate::forUser($actor)->authorize('update', $step->task);
                $this->tasks->updateStatus($actor, $step->task->id, TaskStatus::Completed->value);
            }

            $step->update([
                'status' => 'completed',
                'response_text' => $step->type === SalesPlaybookStepType::Question ? trim((string) $response) : $step->response_text,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            $run = $step->run->fresh('steps') ?? $step->run;
            if ($run->steps->every(fn (OpportunityPlaybookStepRun $item): bool => ! $item->is_required || $item->status === 'completed')) {
                $run->update(['status' => 'completed', 'completed_at' => now()]);
            }

            $this->audit->record(
                $actor,
                $opportunity,
                'sales_playbook.step_completed',
                "Hoàn tất bước playbook '{$step->title}'",
                ['status' => 'pending'],
                ['status' => 'completed', 'step_run_id' => $step->id, 'response_text' => $step->type === SalesPlaybookStepType::Question ? trim((string) $response) : null],
            );

            return $run->fresh(['steps.task', 'playbook', 'stage']) ?? $run;
        });
    }

    private function synchronizeDerivedSteps(OpportunityPlaybookRun $run, Opportunity $opportunity): void
    {
        foreach ($run->steps as $step) {
            $complete = match ($step->type) {
                SalesPlaybookStepType::RequiredField => $this->opportunityFieldIsPresent($opportunity, $step->configuration['field_name'] ?? ''),
                SalesPlaybookStepType::Task, SalesPlaybookStepType::Reminder => $step->task?->status === TaskStatus::Completed,
                default => $step->status === 'completed',
            };

            if ($complete && $step->status !== 'completed') {
                $step->update(['status' => 'completed', 'completed_at' => now()]);
            }
        }
    }

    private function opportunityFieldIsPresent(Opportunity $opportunity, string $field): bool
    {
        if (! in_array($field, ['amount', 'expected_close_date', 'company_id', 'contact_id', 'owner_id', 'notes'], true)) {
            return false;
        }

        $value = $opportunity->getAttribute($field);

        return $field === 'amount'
            ? (float) $value > 0
            : $value !== null && trim((string) $value) !== '';
    }
}
