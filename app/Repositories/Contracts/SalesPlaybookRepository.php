<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\OpportunityPlaybookRun;
use App\Models\PipelineStage;
use App\Models\SalesPlaybook;
use App\Models\StagePlaybookAssignment;
use Illuminate\Database\Eloquent\Collection;

interface SalesPlaybookRepository
{
    /** @return Collection<int, SalesPlaybook> */
    public function allVersions(): Collection;

    public function findOrFail(int $id): SalesPlaybook;

    /** @param array<string, mixed> $data */
    public function create(array $data): SalesPlaybook;

    /** @param array<string, mixed> $data */
    public function update(SalesPlaybook $playbook, array $data): SalesPlaybook;

    /** @param list<array<string, mixed>> $steps */
    public function replaceSteps(SalesPlaybook $playbook, array $steps): SalesPlaybook;

    public function nextVersion(string $code): int;

    public function assignmentForStage(int $stageId): ?StagePlaybookAssignment;

    public function assignStage(PipelineStage $stage, SalesPlaybook $playbook, int $actorId): StagePlaybookAssignment;

    public function createRunIfMissing(array $run, array $stepRuns): OpportunityPlaybookRun;

    public function latestRunForOpportunityStage(int $opportunityId, int $stageId): ?OpportunityPlaybookRun;
}
