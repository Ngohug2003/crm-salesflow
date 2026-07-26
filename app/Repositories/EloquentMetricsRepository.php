<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\ReportFilterData;
use App\Enums\DataScope;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\MetricsRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentMetricsRepository implements MetricsRepository
{
    public function __construct(
        private DataScopeService $dataScope,
    ) {}

    public function getLeadMetrics(User $actor, ReportFilterData $filters): array
    {
        [$start, $end] = $filters->resolveDateRange();

        $query = Lead::query()
            ->whereBetween('leads.created_at', [$start, $end]);

        $this->applyFiltersAndScope($query, $actor, $filters, ownerColumn: 'owner_id', departmentColumn: 'department_id');

        $totalLeads = (int) (clone $query)->count();
        $convertedLeads = (int) (clone $query)->whereNotNull('leads.converted_at')->count();

        $conversionRate = $totalLeads > 0
            ? round(($convertedLeads / $totalLeads) * 100, 2)
            : 0.0;

        /** @var array<string, int> $byStatus */
        $byStatus = (clone $query)
            ->selectRaw('leads.status as lead_status, COUNT(leads.id) as aggregate')
            ->groupBy('leads.status')
            ->pluck('aggregate', 'lead_status')
            ->toArray();

        /** @var array<string, int> $bySource */
        $bySource = (clone $query)
            ->leftJoin('lead_sources', 'leads.lead_source_id', '=', 'lead_sources.id')
            ->selectRaw("COALESCE(lead_sources.name, 'Chưa xác định') as source_name, COUNT(leads.id) as aggregate")
            ->groupBy('source_name')
            ->pluck('aggregate', 'source_name')
            ->toArray();

        return [
            'total_leads' => $totalLeads,
            'converted_leads' => $convertedLeads,
            'conversion_rate' => $conversionRate,
            'by_status' => $byStatus,
            'by_source' => $bySource,
        ];
    }

    public function getOpportunityMetrics(User $actor, ReportFilterData $filters): array
    {
        [$start, $end] = $filters->resolveDateRange();

        $query = Opportunity::query()
            ->whereBetween('opportunities.created_at', [$start, $end]);

        if ($filters->pipelineId !== null) {
            $query->where('opportunities.pipeline_id', $filters->pipelineId);
        }

        $this->applyFiltersAndScope($query, $actor, $filters, ownerColumn: 'owner_id', departmentColumn: 'department_id');

        $totalOpps = (int) (clone $query)->count();
        $openOpps = (int) (clone $query)->where('opportunities.is_won', false)->where('opportunities.is_lost', false)->count();
        $wonOpps = (int) (clone $query)->where('opportunities.is_won', true)->count();
        $lostOpps = (int) (clone $query)->where('opportunities.is_lost', true)->count();

        $totalAmount = (float) (clone $query)->sum('opportunities.amount');
        $openAmount = (float) (clone $query)->where('opportunities.is_won', false)->where('opportunities.is_lost', false)->sum('opportunities.amount');
        $wonAmount = (float) (clone $query)->where('opportunities.is_won', true)->sum('opportunities.amount');

        // Weighted forecast = SUM(amount * probability / 100)
        $weightedVal = (clone $query)
            ->join('pipeline_stages', 'opportunities.stage_id', '=', 'pipeline_stages.id')
            ->selectRaw('SUM(opportunities.amount * pipeline_stages.probability / 100) as weighted_val')
            ->value('weighted_val');

        $weightedForecast = (float) ($weightedVal ?? 0.0);

        $closedCount = $wonOpps + $lostOpps;
        $winRate = $closedCount > 0 ? round(($wonOpps / $closedCount) * 100, 2) : 0.0;

        // Tính số ngày chốt deal trung bình (Sales cycle) cho các deal Won
        $wonDeals = (clone $query)
            ->where('opportunities.is_won', true)
            ->whereNotNull('opportunities.actual_close_date')
            ->get(['opportunities.created_at', 'opportunities.actual_close_date']);

        $totalDays = 0.0;
        foreach ($wonDeals as $deal) {
            if ($deal->actual_close_date !== null && $deal->created_at !== null) {
                $totalDays += max(0, $deal->created_at->diffInDays($deal->actual_close_date));
            }
        }
        $avgSalesCycleDays = $wonDeals->count() > 0
            ? round((float) ($totalDays / $wonDeals->count()), 1)
            : 0.0;

        /** @var array<string, int> $lossReasons */
        $lossReasons = (clone $query)
            ->where('opportunities.is_lost', true)
            ->whereNotNull('opportunities.lost_reason')
            ->selectRaw('opportunities.lost_reason, COUNT(opportunities.id) as aggregate')
            ->groupBy('opportunities.lost_reason')
            ->pluck('aggregate', 'opportunities.lost_reason')
            ->toArray();

        return [
            'total_opportunities' => $totalOpps,
            'open_opportunities' => $openOpps,
            'won_opportunities' => $wonOpps,
            'lost_opportunities' => $lostOpps,
            'total_amount' => round($totalAmount, 2),
            'open_amount' => round($openAmount, 2),
            'won_amount' => round($wonAmount, 2),
            'weighted_forecast' => round($weightedForecast, 2),
            'win_rate' => $winRate,
            'avg_sales_cycle_days' => $avgSalesCycleDays,
            'loss_reasons' => $lossReasons,
        ];
    }

    public function getFunnelMetrics(User $actor, ReportFilterData $filters): array
    {
        [$start, $end] = $filters->resolveDateRange();

        $stageQuery = PipelineStage::query()->orderBy('position');
        if ($filters->pipelineId !== null) {
            $stageQuery->where('pipeline_id', $filters->pipelineId);
        }
        $stages = $stageQuery->get();

        $oppQuery = Opportunity::query()
            ->whereBetween('opportunities.created_at', [$start, $end]);

        if ($filters->pipelineId !== null) {
            $oppQuery->where('opportunities.pipeline_id', $filters->pipelineId);
        }

        $this->applyFiltersAndScope($oppQuery, $actor, $filters, ownerColumn: 'owner_id', departmentColumn: 'department_id');

        /** @var array<int, array{count: int, amount: float}> $oppsByStage */
        $oppsByStage = (clone $oppQuery)
            ->selectRaw('opportunities.stage_id, COUNT(opportunities.id) as count_val, SUM(opportunities.amount) as amount_val')
            ->groupBy('opportunities.stage_id')
            ->get()
            ->keyBy('stage_id')
            ->map(fn ($item) => [
                'count' => (int) $item->getAttribute('count_val'),
                'amount' => (float) ($item->getAttribute('amount_val') ?? 0),
            ])
            ->toArray();

        $result = [];
        $firstCount = 0;
        $prevCount = 0;

        foreach ($stages as $index => $stage) {
            $data = $oppsByStage[$stage->id] ?? ['count' => 0, 'amount' => 0.0];
            $count = $data['count'];

            if ($index === 0) {
                $firstCount = $count;
                $conversionFromTop = 100.0;
                $conversionFromPrev = 100.0;
            } else {
                $conversionFromTop = $firstCount > 0 ? round(($count / $firstCount) * 100, 1) : 0.0;
                $conversionFromPrev = $prevCount > 0 ? round(($count / $prevCount) * 100, 1) : 0.0;
            }

            $prevCount = $count;

            $result[] = [
                'stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'position' => $stage->position,
                'color' => $stage->color,
                'probability' => $stage->probability,
                'opportunity_count' => $count,
                'total_amount' => round($data['amount'], 2),
                'conversion_from_previous' => $conversionFromPrev,
                'conversion_from_top' => $conversionFromTop,
            ];
        }

        return $result;
    }

    public function getActivityAndTaskMetrics(User $actor, ReportFilterData $filters): array
    {
        [$start, $end] = $filters->resolveDateRange();

        // 1. Thống kê Activity
        $actQuery = Activity::query()
            ->whereBetween('activities.created_at', [$start, $end]);

        if ($filters->userId !== null) {
            $actQuery->where('activities.user_id', $filters->userId);
        }

        $scope = $this->dataScope->resolve($actor);
        if ($scope === DataScope::Department) {
            if ($actor->department_id === null) {
                $actQuery->whereRaw('1 = 0');
            } else {
                $deptUserIds = User::query()->where('department_id', $actor->department_id)->pluck('id');
                $actQuery->whereIn('activities.user_id', $deptUserIds);
            }
        } elseif ($scope === DataScope::Owned) {
            $actQuery->where('activities.user_id', $actor->id);
        }

        $totalActivities = (int) (clone $actQuery)->count();

        /** @var array<string, int> $activitiesByType */
        $activitiesByType = (clone $actQuery)
            ->selectRaw('activities.activity_type, COUNT(activities.id) as aggregate')
            ->groupBy('activities.activity_type')
            ->pluck('aggregate', 'activities.activity_type')
            ->toArray();

        // 2. Thống kê Task
        $taskQuery = Task::query()
            ->whereBetween('tasks.created_at', [$start, $end]);

        if ($filters->userId !== null) {
            $taskQuery->where(function (Builder $q) use ($filters): void {
                $q->where('tasks.assigned_to', $filters->userId)
                    ->orWhere('tasks.created_by', $filters->userId);
            });
        }

        if ($scope === DataScope::Department) {
            if ($actor->department_id === null) {
                $taskQuery->whereRaw('1 = 0');
            } else {
                $deptUserIds = User::query()->where('department_id', $actor->department_id)->pluck('id');
                $taskQuery->where(function (Builder $q) use ($deptUserIds): void {
                    $q->whereIn('tasks.assigned_to', $deptUserIds)
                        ->orWhereIn('tasks.created_by', $deptUserIds);
                });
            }
        } elseif ($scope === DataScope::Owned) {
            $taskQuery->where(function (Builder $q) use ($actor): void {
                $q->where('tasks.assigned_to', $actor->id)
                    ->orWhere('tasks.created_by', $actor->id);
            });
        }

        $totalTasks = (int) (clone $taskQuery)->count();
        $completedTasks = (int) (clone $taskQuery)->where('tasks.status', 'completed')->count();
        $overdueTasks = (int) (clone $taskQuery)
            ->where('tasks.due_date', '<', now())
            ->whereNull('tasks.completed_at')
            ->whereNotIn('tasks.status', ['completed', 'cancelled'])
            ->count();

        /** @var array<string, int> $tasksByStatus */
        $tasksByStatus = (clone $taskQuery)
            ->selectRaw('tasks.status, COUNT(tasks.id) as aggregate')
            ->groupBy('tasks.status')
            ->pluck('aggregate', 'tasks.status')
            ->toArray();

        /** @var array<string, int> $tasksByPriority */
        $tasksByPriority = (clone $taskQuery)
            ->selectRaw('tasks.priority, COUNT(tasks.id) as aggregate')
            ->groupBy('tasks.priority')
            ->pluck('aggregate', 'tasks.priority')
            ->toArray();

        return [
            'total_activities' => $totalActivities,
            'activities_by_type' => $activitiesByType,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'tasks_by_status' => $tasksByStatus,
            'tasks_by_priority' => $tasksByPriority,
        ];
    }

    public function getSalesPerformanceMetrics(User $actor, ReportFilterData $filters): array
    {
        [$start, $end] = $filters->resolveDateRange();

        $userQuery = User::query()->with('department:id,name');

        if ($filters->userId !== null) {
            $userQuery->where('users.id', $filters->userId);
        }

        if ($filters->departmentId !== null) {
            $userQuery->where('users.department_id', $filters->departmentId);
        }

        $this->dataScope->apply($userQuery, $actor, ownerColumn: 'id', departmentColumn: 'department_id');

        $users = $userQuery->get();

        $result = [];
        foreach ($users as $user) {
            $totalLeads = Lead::query()
                ->where('owner_id', $user->id)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $oppQuery = Opportunity::query()
                ->where('owner_id', $user->id)
                ->whereBetween('created_at', [$start, $end]);

            if ($filters->pipelineId !== null) {
                $oppQuery->where('pipeline_id', $filters->pipelineId);
            }

            $totalOpps = (int) (clone $oppQuery)->count();
            $wonOpps = (int) (clone $oppQuery)->where('is_won', true)->count();
            $lostOpps = (int) (clone $oppQuery)->where('is_lost', true)->count();
            $wonAmount = (float) (clone $oppQuery)->where('is_won', true)->sum('amount');

            $closedCount = $wonOpps + $lostOpps;
            $winRate = $closedCount > 0 ? round(($wonOpps / $closedCount) * 100, 1) : 0.0;

            $activityCount = Activity::query()
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $completedTasksCount = Task::query()
                ->where('assigned_to', $user->id)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $result[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'department_name' => $user->department !== null ? $user->department->name : 'N/A',
                'total_leads' => $totalLeads,
                'total_opportunities' => $totalOpps,
                'won_opportunities' => $wonOpps,
                'won_amount' => round($wonAmount, 2),
                'win_rate' => $winRate,
                'activity_count' => $activityCount,
                'completed_tasks_count' => $completedTasksCount,
            ];
        }

        usort($result, static fn (array $a, array $b): int => $b['won_amount'] <=> $a['won_amount']);

        return $result;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    private function applyFiltersAndScope(
        Builder $query,
        User $actor,
        ReportFilterData $filters,
        string $ownerColumn = 'owner_id',
        string $departmentColumn = 'department_id',
    ): void {
        if ($filters->userId !== null) {
            $query->where($query->getModel()->qualifyColumn($ownerColumn), $filters->userId);
        }

        if ($filters->departmentId !== null) {
            $query->where($query->getModel()->qualifyColumn($departmentColumn), $filters->departmentId);
        }

        $this->dataScope->apply($query, $actor, $ownerColumn, $departmentColumn);
    }
}
