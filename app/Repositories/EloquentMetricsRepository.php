<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\ReportFilterData;
use App\Models\User;
use App\Repositories\Contracts\MetricsRepository;
use App\Repositories\Metrics\ActivityTaskMetricsQuery;
use App\Repositories\Metrics\FunnelMetricsQuery;
use App\Repositories\Metrics\LeadMetricsQuery;
use App\Repositories\Metrics\OpportunityMetricsQuery;
use App\Repositories\Metrics\SalesPerformanceMetricsQuery;
use App\Services\Analytics\MetricsCacheVersionService;
use App\Services\Authorization\DataScopeService;
use Illuminate\Support\Facades\Cache;

final readonly class EloquentMetricsRepository implements MetricsRepository
{
    private const int CACHE_TTL_SECONDS = 300;

    public function __construct(
        private LeadMetricsQuery $leadMetrics,
        private OpportunityMetricsQuery $opportunityMetrics,
        private FunnelMetricsQuery $funnelMetrics,
        private ActivityTaskMetricsQuery $activityTaskMetrics,
        private SalesPerformanceMetricsQuery $salesPerformanceMetrics,
        private MetricsCacheVersionService $cacheVersion,
        private DataScopeService $dataScope,
    ) {}

    public function clearMetricsCache(User $actor): void
    {
        $this->cacheVersion->bump();
    }

    public function getLeadMetrics(User $actor, ReportFilterData $filters): array
    {
        return $this->remember('leads', $actor, $filters, fn (): array => $this->leadMetrics->execute($actor, $filters));
    }

    public function getOpportunityMetrics(User $actor, ReportFilterData $filters): array
    {
        return $this->remember('opportunities', $actor, $filters, fn (): array => $this->opportunityMetrics->execute($actor, $filters));
    }

    public function getFunnelMetrics(User $actor, ReportFilterData $filters): array
    {
        return $this->remember('funnel', $actor, $filters, fn (): array => $this->funnelMetrics->execute($actor, $filters));
    }

    public function getActivityAndTaskMetrics(User $actor, ReportFilterData $filters): array
    {
        return $this->remember('activity_task', $actor, $filters, fn (): array => $this->activityTaskMetrics->execute($actor, $filters));
    }

    public function getSalesPerformanceMetrics(User $actor, ReportFilterData $filters): array
    {
        return $this->remember('performance', $actor, $filters, fn (): array => $this->salesPerformanceMetrics->execute($actor, $filters));
    }

    /** @template TValue
     * @param  callable(): TValue  $resolver
     * @return TValue
     */
    private function remember(
        string $type,
        User $actor,
        ReportFilterData $filters,
        callable $resolver,
    ): mixed {
        return Cache::remember(
            $this->buildCacheKey($type, $actor, $filters),
            self::CACHE_TTL_SECONDS,
            $resolver,
        );
    }

    private function buildCacheKey(string $type, User $actor, ReportFilterData $filters): string
    {
        $hash = hash('xxh128', (string) json_encode($filters->cachePayload(), JSON_THROW_ON_ERROR));
        $scope = $this->dataScope->resolve($actor)->value;
        $department = $actor->department_id ?? 0;
        $version = $this->cacheVersion->current();

        return "crm_metrics:v{$version}:{$type}:u_{$actor->id}:d_{$department}:s_{$scope}:{$hash}";
    }
}
