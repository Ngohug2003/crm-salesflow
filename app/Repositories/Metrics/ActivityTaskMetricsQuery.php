<?php

declare(strict_types=1);

namespace App\Repositories\Metrics;

use App\Data\ReportFilterData;
use App\Enums\DataScope;
use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Database\Eloquent\Builder;

final readonly class ActivityTaskMetricsQuery
{
    public function __construct(private DataScopeService $dataScope) {}

    /** @return array<string, mixed> */
    public function execute(User $actor, ReportFilterData $filters): array
    {
        [$start, $end] = $filters->resolveDateRange();

        $activityQuery = Activity::query()->whereBetween('activities.created_at', [$start, $end]);
        $this->applyActivityVisibility($activityQuery, $actor, $filters);

        $taskVisibility = Task::query();
        $this->applyTaskVisibility($taskVisibility, $actor, $filters);
        $taskCreated = (clone $taskVisibility)->whereBetween('tasks.created_at', [$start, $end]);
        $taskCompleted = (clone $taskVisibility)
            ->where('tasks.status', 'completed')
            ->whereBetween('tasks.completed_at', [$start, $end]);

        /** @var array<string, int> $activitiesByType */
        $activitiesByType = (clone $activityQuery)
            ->selectRaw('activities.activity_type AS label, COUNT(activities.id) AS aggregate')
            ->groupBy('label')
            ->pluck('aggregate', 'label')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        /** @var array<string, int> $tasksByStatus */
        $tasksByStatus = (clone $taskCreated)
            ->selectRaw('tasks.status AS label, COUNT(tasks.id) AS aggregate')
            ->groupBy('label')
            ->pluck('aggregate', 'label')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        /** @var array<string, int> $tasksByPriority */
        $tasksByPriority = (clone $taskCreated)
            ->selectRaw('tasks.priority AS label, COUNT(tasks.id) AS aggregate')
            ->groupBy('label')
            ->pluck('aggregate', 'label')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        return [
            'total_activities' => (int) (clone $activityQuery)->count(),
            'activities_by_type' => $activitiesByType,
            'total_tasks' => (int) (clone $taskCreated)->count(),
            'completed_tasks' => (int) $taskCompleted->count(),
            'overdue_tasks' => (int) (clone $taskVisibility)
                ->where('tasks.due_date', '<', now())
                ->whereNull('tasks.completed_at')
                ->whereNotIn('tasks.status', ['completed', 'cancelled'])
                ->count(),
            'tasks_by_status' => $tasksByStatus,
            'tasks_by_priority' => $tasksByPriority,
        ];
    }

    /** @param Builder<Activity> $query */
    private function applyActivityVisibility(Builder $query, User $actor, ReportFilterData $filters): void
    {
        if ($filters->userId !== null) {
            $query->where('activities.user_id', $filters->userId);
        }

        $scope = $this->dataScope->resolve($actor);
        if ($scope === DataScope::Department) {
            $query->whereIn('activities.user_id', User::query()
                ->select('id')
                ->where('department_id', $actor->department_id ?? 0));
        } elseif ($scope === DataScope::Owned) {
            $query->where('activities.user_id', $actor->getKey());
        }
    }

    /** @param Builder<Task> $query */
    private function applyTaskVisibility(Builder $query, User $actor, ReportFilterData $filters): void
    {
        if ($filters->userId !== null) {
            $this->whereTaskRelatedToUsers($query, [$filters->userId]);
        }

        $scope = $this->dataScope->resolve($actor);
        if ($scope === DataScope::Department) {
            $userIds = User::query()
                ->select('id')
                ->where('department_id', $actor->department_id ?? 0)
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();
            $this->whereTaskRelatedToUsers($query, $userIds);
        } elseif ($scope === DataScope::Owned) {
            $this->whereTaskRelatedToUsers($query, [(int) $actor->getKey()]);
        }
    }

    /** @param Builder<Task> $query
     * @param  list<int>  $userIds
     */
    private function whereTaskRelatedToUsers(Builder $query, array $userIds): void
    {
        $query->where(function (Builder $taskQuery) use ($userIds): void {
            $taskQuery->whereIn('tasks.created_by', $userIds)
                ->orWhereIn('tasks.assigned_to', $userIds)
                ->orWhereHas('assignees', fn (Builder $users): Builder => $users->whereKey($userIds));
        });
    }
}
