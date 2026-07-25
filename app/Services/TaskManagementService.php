<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TaskFilterData;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class TaskManagementService
{
    public function __construct(
        private TaskRepository $tasks,
        private SystemAuditService $audit,
    ) {}

    public function get(User $actor, int $id): Task
    {
        $task = $this->tasks->findVisibleForUserOrFail($actor, $id);
        Gate::forUser($actor)->authorize('view', $task);

        return $task;
    }

    /** @return LengthAwarePaginator<int, Task> */
    public function list(User $actor, TaskFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Task::class);

        return $this->tasks->paginateForUser($actor, $filters, $perPage);
    }

    /** @return Collection<int, Task> */
    public function listForSubject(User $actor, string $subjectType, int $subjectId): Collection
    {
        Gate::forUser($actor)->authorize('viewAny', Task::class);

        return $this->tasks->getForSubject($actor, $subjectType, $subjectId);
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): Task
    {
        Gate::forUser($actor)->authorize('create', Task::class);

        return DB::transaction(function () use ($actor, $data): Task {
            $taskData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? TaskStatus::Todo->value,
                'priority' => $data['priority'] ?? TaskPriority::Medium->value,
                'due_date' => $data['due_date'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? $actor->id,
                'created_by' => $actor->id,
                'subject_type' => $data['subject_type'] ?? null,
                'subject_id' => $data['subject_id'] ?? null,
            ];

            if ($taskData['status'] === TaskStatus::Completed->value) {
                $taskData['completed_at'] = now();
            }

            $task = $this->tasks->create($taskData);

            $this->audit->record(
                actor: $actor,
                subject: $task,
                event: 'created',
                description: "Tạo công việc '{$task->title}'",
                old: null,
                new: $task->toArray(),
            );

            return $task;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, int $id, array $data): Task
    {
        $task = $this->tasks->findVisibleForUserOrFail($actor, $id);
        Gate::forUser($actor)->authorize('update', $task);

        return DB::transaction(function () use ($actor, $task, $data): Task {
            $before = $task->toArray();

            $updateData = [];
            if (array_key_exists('title', $data)) {
                $updateData['title'] = $data['title'];
            }
            if (array_key_exists('description', $data)) {
                $updateData['description'] = $data['description'];
            }
            if (array_key_exists('priority', $data)) {
                $updateData['priority'] = $data['priority'];
            }
            if (array_key_exists('due_date', $data)) {
                $updateData['due_date'] = $data['due_date'];
            }
            if (array_key_exists('assigned_to', $data)) {
                $updateData['assigned_to'] = $data['assigned_to'];
            }
            if (array_key_exists('status', $data)) {
                $updateData['status'] = $data['status'];
                if ($data['status'] === TaskStatus::Completed->value && $task->completed_at === null) {
                    $updateData['completed_at'] = now();
                } elseif ($data['status'] !== TaskStatus::Completed->value) {
                    $updateData['completed_at'] = null;
                }
            }

            $updatedTask = $this->tasks->update($task, $updateData);

            $this->audit->record(
                actor: $actor,
                subject: $updatedTask,
                event: 'updated',
                description: "Cập nhật công việc '{$updatedTask->title}'",
                old: $before,
                new: $updatedTask->toArray(),
            );

            return $updatedTask;
        });
    }

    public function toggleStatus(User $actor, int $id): Task
    {
        $task = $this->tasks->findVisibleForUserOrFail($actor, $id);
        Gate::forUser($actor)->authorize('update', $task);

        $newStatus = $task->status === TaskStatus::Completed ? TaskStatus::Todo->value : TaskStatus::Completed->value;

        return $this->update($actor, $id, ['status' => $newStatus]);
    }

    public function updateStatus(User $actor, int $id, string $status): Task
    {
        $task = $this->tasks->findVisibleForUserOrFail($actor, $id);
        Gate::forUser($actor)->authorize('update', $task);

        return $this->update($actor, $id, ['status' => $status]);
    }

    public function delete(User $actor, int $id): bool
    {
        $task = $this->tasks->findVisibleForUserOrFail($actor, $id);
        Gate::forUser($actor)->authorize('delete', $task);

        return DB::transaction(function () use ($actor, $task): bool {
            $before = $task->toArray();
            $result = $this->tasks->delete($task);

            $this->audit->record(
                actor: $actor,
                subject: $task,
                event: 'deleted',
                description: "Xóa công việc '{$task->title}'",
                old: $before,
                new: null,
            );

            return $result;
        });
    }
}
