<?php

declare(strict_types=1);

namespace App\Repositories\Metrics;

use App\Data\ReportFilterData;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final readonly class OpportunityMetricsQuery
{
    public function __construct(private MetricQueryScope $scope) {}

    /** @return array<string, mixed> */
    public function execute(User $actor, ReportFilterData $filters): array
    {
        [$start, $end] = $filters->resolveDateRange();

        $base = Opportunity::query();
        if ($filters->pipelineId !== null) {
            $base->where('opportunities.pipeline_id', $filters->pipelineId);
        }
        $this->scope->apply($base, $actor, $filters);

        $created = (clone $base)->whereBetween('opportunities.created_at', [$start, $end]);
        $closed = (clone $base)
            ->whereBetween('opportunities.actual_close_date', [$start->toDateString(), $end->toDateString()])
            ->where(fn (Builder $query): Builder => $query->where('opportunities.is_won', true)->orWhere('opportunities.is_lost', true));
        $forecast = (clone $base)
            ->where('opportunities.is_won', false)
            ->where('opportunities.is_lost', false)
            ->whereBetween('opportunities.expected_close_date', [$start->toDateString(), $end->toDateString()]);

        $createdSummary = (clone $created)
            ->selectRaw('COUNT(opportunities.id) AS total')
            ->selectRaw('COALESCE(SUM(opportunities.amount), 0) AS amount')
            ->first();

        $closedSummary = (clone $closed)
            ->selectRaw('COUNT(*) FILTER (WHERE opportunities.is_won = true) AS won_count')
            ->selectRaw('COUNT(*) FILTER (WHERE opportunities.is_lost = true) AS lost_count')
            ->selectRaw('COALESCE(SUM(opportunities.amount) FILTER (WHERE opportunities.is_won = true), 0) AS won_amount')
            ->first();

        $forecastSummary = (clone $forecast)
            ->join('pipeline_stages', 'opportunities.stage_id', '=', 'pipeline_stages.id')
            ->selectRaw('COUNT(opportunities.id) AS open_count')
            ->selectRaw('COALESCE(SUM(opportunities.amount), 0) AS open_amount')
            ->selectRaw('COALESCE(SUM(opportunities.amount * pipeline_stages.probability / 100.0), 0) AS weighted_forecast')
            ->first();

        $wonCount = (int) ($closedSummary?->getAttribute('won_count') ?? 0);
        $lostCount = (int) ($closedSummary?->getAttribute('lost_count') ?? 0);
        $closedCount = $wonCount + $lostCount;

        $wonDeals = (clone $closed)
            ->where('opportunities.is_won', true)
            ->get(['opportunities.created_at', 'opportunities.actual_close_date']);

        $averageCycle = $wonDeals->avg(static function (Opportunity $opportunity): float {
            if ($opportunity->created_at === null || $opportunity->actual_close_date === null) {
                return 0.0;
            }

            return (float) max(0, $opportunity->created_at->diffInDays($opportunity->actual_close_date));
        });

        /** @var array<string, int> $lossReasons */
        $lossReasons = (clone $closed)
            ->where('opportunities.is_lost', true)
            ->whereNotNull('opportunities.lost_reason')
            ->selectRaw('opportunities.lost_reason AS label, COUNT(opportunities.id) AS aggregate')
            ->groupBy('label')
            ->pluck('aggregate', 'label')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        return [
            'total_opportunities' => (int) ($createdSummary?->getAttribute('total') ?? 0),
            'open_opportunities' => (int) ($forecastSummary?->getAttribute('open_count') ?? 0),
            'won_opportunities' => $wonCount,
            'lost_opportunities' => $lostCount,
            'total_amount' => round((float) ($createdSummary?->getAttribute('amount') ?? 0), 2),
            'open_amount' => round((float) ($forecastSummary?->getAttribute('open_amount') ?? 0), 2),
            'won_amount' => round((float) ($closedSummary?->getAttribute('won_amount') ?? 0), 2),
            'weighted_forecast' => round((float) ($forecastSummary?->getAttribute('weighted_forecast') ?? 0), 2),
            'win_rate' => $closedCount > 0 ? round(($wonCount / $closedCount) * 100, 2) : 0.0,
            'avg_sales_cycle_days' => round((float) ($averageCycle ?? 0), 1),
            'loss_reasons' => $lossReasons,
            'forecast_by_stage' => $this->forecastByStage($forecast),
            'revenue_series' => $this->revenueSeries($closed, $forecast),
        ];
    }

    /** @return array<int, array<string, int|float|string>> */
    private function forecastByStage(Builder $forecast): array
    {
        return (clone $forecast)
            ->join('pipeline_stages', 'opportunities.stage_id', '=', 'pipeline_stages.id')
            ->selectRaw('pipeline_stages.id, pipeline_stages.name, pipeline_stages.position, pipeline_stages.probability')
            ->selectRaw('COUNT(opportunities.id) AS opportunity_count')
            ->selectRaw('COALESCE(SUM(opportunities.amount), 0) AS total_amount')
            ->selectRaw('COALESCE(SUM(opportunities.amount * pipeline_stages.probability / 100.0), 0) AS weighted_amount')
            ->groupBy('pipeline_stages.id', 'pipeline_stages.name', 'pipeline_stages.position', 'pipeline_stages.probability')
            ->orderBy('pipeline_stages.position')
            ->get()
            ->map(static fn (object $row): array => [
                'stage_id' => (int) $row->getAttribute('id'),
                'stage_name' => (string) $row->getAttribute('name'),
                'probability' => (int) $row->getAttribute('probability'),
                'opportunity_count' => (int) $row->getAttribute('opportunity_count'),
                'total_amount' => round((float) $row->getAttribute('total_amount'), 2),
                'weighted_amount' => round((float) $row->getAttribute('weighted_amount'), 2),
            ])
            ->all();
    }

    /** @return array{labels: list<string>, won: list<float>, forecast: list<float>} */
    private function revenueSeries(Builder $closed, Builder $forecast): array
    {
        $wonByMonth = (clone $closed)
            ->where('opportunities.is_won', true)
            ->get(['opportunities.actual_close_date', 'opportunities.amount'])
            ->groupBy(static fn (Model $item): string => Carbon::parse(
                (string) $item->getAttribute('actual_close_date')
            )->format('Y-m'))
            ->map(static fn ($rows): float => round((float) $rows->sum('amount'), 2));

        $forecastByMonth = (clone $forecast)
            ->join('pipeline_stages', 'opportunities.stage_id', '=', 'pipeline_stages.id')
            ->get(['opportunities.expected_close_date', 'opportunities.amount', 'pipeline_stages.probability'])
            ->groupBy(static fn (Model $item): string => Carbon::parse(
                (string) $item->getAttribute('expected_close_date')
            )->format('Y-m'))
            ->map(static fn ($rows): float => round((float) $rows->sum(
                static fn (Model $item): float => (
                    (float) $item->getAttribute('amount')
                    * (int) $item->getAttribute('probability')
                ) / 100
            ), 2));

        $months = $wonByMonth->keys()
            ->merge($forecastByMonth->keys())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'labels' => $months->map(static fn (string $month): string => Carbon::createFromFormat('Y-m', $month)->format('m/Y'))->all(),
            'won' => $months->map(static fn (string $month): float => (float) $wonByMonth->get($month, 0))->all(),
            'forecast' => $months->map(static fn (string $month): float => (float) $forecastByMonth->get($month, 0))->all(),
        ];
    }
}
