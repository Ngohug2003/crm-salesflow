<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\TaskFilterData;
use App\Enums\DataScope;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class EloquentTaskRepository implements TaskRepository
{
    public function __construct(
        private DataScopeService $dataScope,
    ) {}

    public function findById(int $id): ?Task
    {
        return Task::query()
            ->with(['assignee', 'creator', 'subject'])
            ->find($id);
    }

    public function findVisibleForUserOrFail(User $user, int $id): Task
    {
        $query = Task::query()->with(['assignee', 'creator', 'subject']);
        $this->applyDataScope($query, $user);

        /** @var Task */
        return $query->findOrFail($id);
    }

    public function paginateForUser(User $user, TaskFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::query()->with(['assignee', 'creator', 'subject']);

        $this->applyDataScope($query, $user);
        $this->applyFilters($query, $filters);

        /** @var LengthAwarePaginator<int, Task> */
        return $query->paginate($perPage);
    }

    public function getForSubject(User $user, string $subjectType, int $subjectId): Collection
    {
        $query = Task::query()
            ->with(['assignee', 'creator'])
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId);

        $this->applyDataScope($query, $user);

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function create(array $data): Task
    {
        /** @var Task */
        return Task::query()->create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh(['assignee', 'creator', 'subject']) ?? $task;
    }

    public function delete(Task $task): bool
    {
        return (bool) $task->delete();
    }

    /** @param Builder<Task> $query */
    private function applyDataScope(Builder $query, User $user): void
    {
        $scope = $this->dataScope->resolve($user);

        if ($scope === DataScope::All || $scope === DataScope::ReadOnly) {
            return;
        }

        if ($scope === DataScope::Department) {
            $departmentUserIds = User::query()
                ->where('department_id', $user->department_id)
                ->pluck('id');

            $query->where(function (Builder $q) use ($departmentUserIds, $user): void {
                $q->whereIn('assigned_to', $departmentUserIds)
                    ->orWhereIn('created_by', $departmentUserIds)
                    ->orWhere('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            });

            return;
        }

        // Default to Owned (assigned or created by user)
        $query->where(function (Builder $q) use ($user): void {
            $q->where('assigned_to', $user->id)
                ->orWhere('created_by', $user->id);
        });
    }

    /** @param Builder<Task> $query */
    private function applyFilters(Builder $query, TaskFilterData $filters): void
    {
        if ($filters->search !== null && trim($filters->search) !== '') {
            $search = trim($filters->search);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($filters->status !== null && $filters->status !== '') {
            $query->where('status', $filters->status);
        }

        if ($filters->priority !== null && $filters->priority !== '') {
            $query->where('priority', $filters->priority);
        }

        if ($filters->assignedTo !== null) {
            $query->where('assigned_to', $filters->assignedTo);
        }

        if ($filters->subjectType !== null && $filters->subjectType !== '') {
            $query->where('subject_type', $filters->subjectType);
            if ($filters->subjectId !== null) {
                $query->where('subject_id', $filters->subjectId);
            }
        }

        if ($filters->overdue === true) {
            $query->whereNull('completed_at')
                ->where('due_date', '<', now());
        }

        $sortableFields = ['created_at', 'due_date', 'priority', 'status', 'title'];
        $sortBy = in_array($filters->sortBy, $sortableFields, true) ? $filters->sortBy : 'created_at';
        $sortDirection = strtolower($filters->sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);
    }
}
