<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\OpportunityPlaybookRun;
use App\Models\PipelineStage;
use App\Models\SalesPlaybook;
use App\Models\StagePlaybookAssignment;
use App\Repositories\Contracts\SalesPlaybookRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentSalesPlaybookRepository implements SalesPlaybookRepository
{
    public function allVersions(): Collection
    {
        return SalesPlaybook::query()
            ->with(['steps', 'assignments.stage.pipeline'])
            ->orderBy('name')
            ->orderByDesc('version')
            ->get();
    }

    public function findOrFail(int $id): SalesPlaybook
    {
        return SalesPlaybook::query()
            ->with(['steps', 'assignments.stage.pipeline'])
            ->findOrFail($id);
    }

    public function create(array $data): SalesPlaybook
    {
        return SalesPlaybook::query()->create($data);
    }

    public function update(SalesPlaybook $playbook, array $data): SalesPlaybook
    {
        $playbook->update($data);

        return $this->findOrFail($playbook->id);
    }

    public function replaceSteps(SalesPlaybook $playbook, array $steps): SalesPlaybook
    {
        $playbook->steps()->delete();
        $playbook->steps()->createMany($steps);

        return $this->findOrFail($playbook->id);
    }

    public function nextVersion(string $code): int
    {
        return ((int) SalesPlaybook::withTrashed()->where('code', $code)->max('version')) + 1;
    }

    public function assignmentForStage(int $stageId): ?StagePlaybookAssignment
    {
        return StagePlaybookAssignment::query()
            ->with(['playbook.steps', 'stage'])
            ->where('pipeline_stage_id', $stageId)
            ->where('is_active', true)
            ->first();
    }

    public function assignStage(PipelineStage $stage, SalesPlaybook $playbook, int $actorId): StagePlaybookAssignment
    {
        return StagePlaybookAssignment::query()->updateOrCreate(
            ['pipeline_stage_id' => $stage->id],
            [
                'sales_playbook_id' => $playbook->id,
                'assigned_by' => $actorId,
                'is_active' => true,
            ],
        );
    }

    public function createRunIfMissing(array $run, array $stepRuns): OpportunityPlaybookRun
    {
        return DB::transaction(function () use ($run, $stepRuns): OpportunityPlaybookRun {
            $existing = OpportunityPlaybookRun::query()
                ->with(['steps.task', 'playbook', 'stage'])
                ->where('idempotency_key', $run['idempotency_key'])
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $created = OpportunityPlaybookRun::query()->create($run);
            $created->steps()->createMany($stepRuns);

            return $created->load(['steps.task', 'playbook', 'stage']);
        });
    }

    public function latestRunForOpportunityStage(int $opportunityId, int $stageId): ?OpportunityPlaybookRun
    {
        return OpportunityPlaybookRun::query()
            ->with(['steps.task', 'playbook', 'stage', 'opportunity'])
            ->where('opportunity_id', $opportunityId)
            ->where('pipeline_stage_id', $stageId)
            ->latest('id')
            ->first();
    }
}
