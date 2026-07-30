<?php

declare(strict_types=1);

namespace App\Repositories\Metrics;

use App\Data\ReportFilterData;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

final readonly class LeadMetricsQuery
{
    public function __construct(private MetricQueryScope $scope) {}

    /** @return array<string, mixed> */
    public function execute(User $actor, ReportFilterData $filters): array
    {
        [$start, $end] = $filters->resolveDateRange();
        $query = Lead::query()->whereBetween('leads.created_at', [$start, $end]);
        $this->scope->apply($query, $actor, $filters);

        $summary = (clone $query)
            ->selectRaw('COUNT(leads.id) AS total')
            ->selectRaw('COUNT(leads.converted_at) AS converted')
            ->first();

        $total = (int) ($summary?->getAttribute('total') ?? 0);
        $converted = (int) ($summary?->getAttribute('converted') ?? 0);

        /** @var array<string, int> $rawByStatus */
        $rawByStatus = (clone $query)
            ->selectRaw('leads.status AS label, COUNT(leads.id) AS aggregate')
            ->groupBy('leads.status')
            ->pluck('aggregate', 'label')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
        $byStatus = [];
        foreach ($rawByStatus as $status => $count) {
            $byStatus[LeadStatus::tryFrom((string) $status)?->label() ?? (string) $status] = $count;
        }

        /** @var array<string, int> $bySource */
        $bySource = (clone $query)
            ->leftJoin('lead_sources', 'leads.lead_source_id', '=', 'lead_sources.id')
            ->selectRaw("COALESCE(lead_sources.name, 'Chưa xác định') AS label, COUNT(leads.id) AS aggregate")
            ->groupBy('label')
            ->pluck('aggregate', 'label')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        return [
            'total_leads' => $total,
            'converted_leads' => $converted,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 2) : 0.0,
            'by_status' => $byStatus,
            'by_source' => $bySource,
        ];
    }
}
