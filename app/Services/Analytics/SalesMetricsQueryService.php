<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Data\ReportFilterData;
use App\Models\User;
use App\Repositories\Contracts\MetricsRepository;

final readonly class SalesMetricsQueryService
{
    public function __construct(
        private MetricsRepository $metrics,
    ) {}

    /**
     * Lấy các chỉ số thống kê Lead
     *
     * @return array{
     *     total_leads: int,
     *     converted_leads: int,
     *     conversion_rate: float,
     *     by_status: array<string, int>,
     *     by_source: array<string, int>
     * }
     */
    public function getLeadMetrics(User $actor, ReportFilterData $filters): array
    {
        return $this->metrics->getLeadMetrics($actor, $filters);
    }

    /**
     * Lấy các chỉ số thống kê Cơ hội bán hàng & Doanh thu
     *
     * @return array{
     *     total_opportunities: int,
     *     open_opportunities: int,
     *     won_opportunities: int,
     *     lost_opportunities: int,
     *     total_amount: float,
     *     open_amount: float,
     *     won_amount: float,
     *     weighted_forecast: float,
     *     win_rate: float,
     *     avg_sales_cycle_days: float,
     *     loss_reasons: array<string, int>
     * }
     */
    public function getOpportunityMetrics(User $actor, ReportFilterData $filters): array
    {
        return $this->metrics->getOpportunityMetrics($actor, $filters);
    }

    /**
     * Lấy các chỉ số Phễu bán hàng (Funnel Report) theo từng Stage của Pipeline
     *
     * @return array<int, array{
     *     stage_id: int,
     *     stage_name: string,
     *     position: int,
     *     color: string,
     *     probability: int,
     *     opportunity_count: int,
     *     total_amount: float,
     *     conversion_from_previous: float,
     *     conversion_from_top: float
     * }>
     */
    public function getFunnelMetrics(User $actor, ReportFilterData $filters): array
    {
        return $this->metrics->getFunnelMetrics($actor, $filters);
    }

    /**
     * Lấy các chỉ số Hoạt động & Công việc (Activities & Tasks)
     *
     * @return array{
     *     total_activities: int,
     *     activities_by_type: array<string, int>,
     *     total_tasks: int,
     *     completed_tasks: int,
     *     overdue_tasks: int,
     *     tasks_by_status: array<string, int>,
     *     tasks_by_priority: array<string, int>
     * }
     */
    public function getActivityAndTaskMetrics(User $actor, ReportFilterData $filters): array
    {
        return $this->metrics->getActivityAndTaskMetrics($actor, $filters);
    }

    /**
     * Tổng hợp nhanh Dashboard Overview Summary
     *
     * @return array{
     *     lead_metrics: array<string, mixed>,
     *     opportunity_metrics: array<string, mixed>,
     *     activity_task_metrics: array<string, mixed>
     * }
     */
    public function getOverviewSummary(User $actor, ReportFilterData $filters): array
    {
        return [
            'lead_metrics' => $this->getLeadMetrics($actor, $filters),
            'opportunity_metrics' => $this->getOpportunityMetrics($actor, $filters),
            'activity_task_metrics' => $this->getActivityAndTaskMetrics($actor, $filters),
        ];
    }
}
