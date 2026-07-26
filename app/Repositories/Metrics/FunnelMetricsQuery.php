<?php

declare(strict_types=1);

namespace App\Repositories\Metrics;

use App\Data\ReportFilterData;
use App\Enums\DataScope;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class FunnelMetricsQuery
{
    public function __construct(
        private MetricQueryScope $scope,
        private DataScopeService $dataScope,
    ) {}

    /** @return array<int, array<string, int|float|string>> */
    public function execute(User $actor, ReportFilterData $filters): array
    {
        if ($filters->pipelineId === null || ! $this->canViewPipeline($actor, $filters->pipelineId)) {
            return [];
        }

        [$start, $end] = $filters->resolveDateRange();
        $stages = PipelineStage::query()
            ->where('pipeline_id', $filters->pipelineId)
            ->orderBy('position')
            ->get(['id', 'name', 'position', 'color', 'probability']);

        $opportunityQuery = Opportunity::query()
            ->where('opportunities.pipeline_id', $filters->pipelineId)
            ->whereBetween('opportunities.created_at', [$start, $end]);
        $this->scope->apply($opportunityQuery, $actor, $filters);

        $opportunities = $opportunityQuery->get(['id', 'stage_id', 'amount']);
        $histories = OpportunityStageHistory::query()
            ->whereIn('opportunity_id', $opportunities->modelKeys())
            ->get(['opportunity_id', 'from_stage_id', 'to_stage_id'])
            ->groupBy('opportunity_id');

        $reachedByOpportunity = [];
        foreach ($opportunities as $opportunity) {
            $reached = [(int) $opportunity->stage_id];
            foreach ($histories->get($opportunity->id, collect()) as $history) {
                if ($history->from_stage_id !== null) {
                    $reached[] = (int) $history->from_stage_id;
                }
                $reached[] = (int) $history->to_stage_id;
            }
            $reachedByOpportunity[$opportunity->id] = array_unique($reached);
        }

        $result = [];
        $firstCount = 0;
        $previousCount = 0;

        foreach ($stages as $index => $stage) {
            $reached = $opportunities->filter(
                static fn (Opportunity $opportunity): bool => in_array(
                    (int) $stage->id,
                    $reachedByOpportunity[$opportunity->id] ?? [],
                    true,
                )
            );
            $count = $reached->count();

            if ($index === 0) {
                $firstCount = $count;
            }

            $result[] = [
                'stage_id' => (int) $stage->id,
                'stage_name' => (string) $stage->name,
                'position' => (int) $stage->position,
                'color' => (string) $stage->color,
                'probability' => (int) $stage->probability,
                'opportunity_count' => $count,
                'total_amount' => round((float) $reached->sum('amount'), 2),
                'conversion_from_previous' => $index === 0
                    ? ($count > 0 ? 100.0 : 0.0)
                    : $this->percentage($count, $previousCount),
                'conversion_from_top' => $index === 0
                    ? ($count > 0 ? 100.0 : 0.0)
                    : $this->percentage($count, $firstCount),
            ];
            $previousCount = $count;
        }

        return $result;
    }

    private function canViewPipeline(User $actor, int $pipelineId): bool
    {
        $pipelineExists = Pipeline::query()->active()->whereKey($pipelineId)->exists();
        if (! $pipelineExists) {
            return false;
        }

        if (in_array($this->dataScope->resolve($actor), [DataScope::All, DataScope::ReadOnly], true)) {
            return true;
        }

        $query = Opportunity::query()->where('pipeline_id', $pipelineId);
        $this->dataScope->apply($query, $actor);

        return $query->exists();
    }

    private function percentage(int $value, int $base): float
    {
        return $base > 0 ? min(100.0, round(($value / $base) * 100, 1)) : 0.0;
    }
}
