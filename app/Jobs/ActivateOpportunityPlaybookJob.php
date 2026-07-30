<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Opportunity;
use App\Models\User;
use App\Services\OpportunityPlaybookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ActivateOpportunityPlaybookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 90];

    public function __construct(
        public int $actorId,
        public int $opportunityId,
        public int $stageId,
        public int $stageEntryId,
    ) {
        $this->onQueue('automation');
    }

    public function handle(OpportunityPlaybookService $playbooks): void
    {
        $actor = User::query()->find($this->actorId);
        $opportunity = Opportunity::query()->find($this->opportunityId);

        if ($actor === null || $opportunity === null || $opportunity->stage_id !== $this->stageId) {
            return;
        }

        $playbooks->activateForStage(
            $actor,
            $opportunity,
            $this->stageId,
            $this->stageEntryId,
        );
    }
}
