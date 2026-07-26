<?php

declare(strict_types=1);

namespace App\Repositories\Metrics;

use App\Data\ReportFilterData;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Support\Facades\DB;

final readonly class SalesPerformanceMetricsQuery
{
    public function __construct(private DataScopeService $dataScope) {}

    /** @return array<int, array<string, int|float|string>> */
    public function execute(User $actor, ReportFilterData $filters): array
    {
        [$start, $end] = $filters->resolveDateRange();
        $userQuery = User::query()->with('department:id,name')->orderBy('name');

        if ($filters->userId !== null) {
            $userQuery->whereKey($filters->userId);
        }
        if ($filters->departmentId !== null) {
            $userQuery->where('users.department_id', $filters->departmentId);
        }
        $this->dataScope->apply($userQuery, $actor, ownerColumn: 'id');

        $users = $userQuery->get(['id', 'name', 'department_id']);
        $userIds = $users->modelKeys();
        if ($userIds === []) {
            return [];
        }

        $leadCounts = Lead::query()
            ->whereIn('owner_id', $userIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('owner_id, COUNT(id) AS aggregate')
            ->groupBy('owner_id')
            ->pluck('aggregate', 'owner_id');

        $createdOpportunityQuery = Opportunity::query()
            ->whereIn('owner_id', $userIds)
            ->whereBetween('created_at', [$start, $end]);
        $closedOpportunityQuery = Opportunity::query()
            ->whereIn('owner_id', $userIds)
            ->whereBetween('actual_close_date', [$start->toDateString(), $end->toDateString()]);
        if ($filters->pipelineId !== null) {
            $createdOpportunityQuery->where('pipeline_id', $filters->pipelineId);
            $closedOpportunityQuery->where('pipeline_id', $filters->pipelineId);
        }

        $opportunityCounts = $createdOpportunityQuery
            ->selectRaw('owner_id, COUNT(id) AS aggregate')
            ->groupBy('owner_id')
            ->pluck('aggregate', 'owner_id');

        $closedMetrics = $closedOpportunityQuery
            ->selectRaw('owner_id')
            ->selectRaw('COUNT(*) FILTER (WHERE is_won = true) AS won_count')
            ->selectRaw('COUNT(*) FILTER (WHERE is_lost = true) AS lost_count')
            ->selectRaw('COALESCE(SUM(amount) FILTER (WHERE is_won = true), 0) AS won_amount')
            ->groupBy('owner_id')
            ->get()
            ->keyBy('owner_id');

        $activityCounts = Activity::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('user_id, COUNT(id) AS aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');

        $completedTaskCounts = DB::table('task_assignees')
            ->join('tasks', 'tasks.id', '=', 'task_assignees.task_id')
            ->whereNull('tasks.deleted_at')
            ->where('tasks.status', 'completed')
            ->whereIn('task_assignees.user_id', $userIds)
            ->whereBetween('tasks.completed_at', [$start, $end])
            ->selectRaw('task_assignees.user_id, COUNT(DISTINCT tasks.id) AS aggregate')
            ->groupBy('task_assignees.user_id')
            ->pluck('aggregate', 'task_assignees.user_id');

        return $users
            ->map(function (User $user) use (
                $leadCounts,
                $opportunityCounts,
                $closedMetrics,
                $activityCounts,
                $completedTaskCounts,
            ): array {
                $closed = $closedMetrics->get($user->id);
                $won = (int) ($closed?->getAttribute('won_count') ?? 0);
                $lost = (int) ($closed?->getAttribute('lost_count') ?? 0);
                $closedCount = $won + $lost;

                return [
                    'user_id' => (int) $user->id,
                    'user_name' => (string) $user->name,
                    'department_name' => $user->department_id !== null
                        ? (string) $user->department->name
                        : 'Chưa phân phòng ban',
                    'total_leads' => (int) $leadCounts->get($user->id, 0),
                    'total_opportunities' => (int) $opportunityCounts->get($user->id, 0),
                    'won_opportunities' => $won,
                    'won_amount' => round((float) ($closed?->getAttribute('won_amount') ?? 0), 2),
                    'win_rate' => $closedCount > 0 ? round(($won / $closedCount) * 100, 1) : 0.0,
                    'activity_count' => (int) $activityCounts->get($user->id, 0),
                    'completed_tasks_count' => (int) $completedTaskCounts->get($user->id, 0),
                ];
            })
            ->sortByDesc('won_amount')
            ->values()
            ->all();
    }
}
