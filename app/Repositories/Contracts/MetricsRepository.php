<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\ReportFilterData;
use App\Models\User;

interface MetricsRepository
{
    /**
     * @return array{
     *     total_leads: int,
     *     converted_leads: int,
     *     conversion_rate: float,
     *     by_status: array<string, int>,
     *     by_source: array<string, int>
     * }
     */
    public function getLeadMetrics(User $actor, ReportFilterData $filters): array;

    /**
     * @return array{
     *     total_opportunities: int,
     *     open_opportunities: int,
     *     won_opportunities: int,
     *     lost_opportunities: int,
     *     total_amount: float,
     *     won_amount: float,
     *     weighted_forecast: float,
     *     win_rate: float,
     *     avg_sales_cycle_days: float,
     *     loss_reasons: array<string, int>
     * }
     */
    public function getOpportunityMetrics(User $actor, ReportFilterData $filters): array;

    /**
     * @return array<int, array{
     *     stage_id: int,
     *     stage_name: string,
     *     position: int,
     *     color: string,
     *     probability: int,
     *     opportunity_count: int,
     *     total_amount: float
     * }>
     */
    public function getFunnelMetrics(User $actor, ReportFilterData $filters): array;

    /**
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
    public function getActivityAndTaskMetrics(User $actor, ReportFilterData $filters): array;
}
