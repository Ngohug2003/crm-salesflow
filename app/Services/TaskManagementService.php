<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TaskFilterData;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class TaskManagementService
{
    public function __construct(
        private TaskRepository $tasks,
        private UserRepository $users,
        private TaskSubjectService $subjects,
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

        $status = (string) ($data['status'] ?? TaskStatus::Todo->value);
        $priority = (string) ($data['priority'] ?? TaskPriority::Medium->value);
        $this->ensureValidStatus($status);
        $this->ensureValidPriority($priority);

        $assignedTo = isset($data['assigned_to']) && $data['assigned_to'] !== ''
            ? (int) $data['assigned_to']
            : (int) $actor->getKey();
        $assigneeIds = $this->visibleAssigneeIds(
            $actor,
            array_merge((array) ($data['assignee_ids'] ?? []), [$assignedTo]),
        );
        $subjectType = isset($data['subject_type']) && $data['subject_type'] !== ''
            ? (string) $data['subject_type']
            : null;
        $subjectId = isset($data['subject_id']) && (int) $data['subject_id'] > 0
            ? (int) $data['subject_id']
            : null;
        $this->subjects->validateVisibleSubject($actor, $subjectType, $subjectId);

        return DB::transaction(function () use ($actor, $data, $status, $priority, $assignedTo, $assigneeIds, $subjectType, $subjectId): Task {
            $taskData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => $status,
                'priority' => $priority,
                'due_date' => $data['due_date'] ?? null,
                'reminder_at' => $data['reminder_at'] ?? null,
                'assigned_to' => $assignedTo,
                'created_by' => $actor->id,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
            ];

            if ($taskData['status'] === TaskStatus::Completed->value) {
                $taskData['completed_at'] = now();
            }

            $task = $this->tasks->create($taskData);

            $task->assignees()->sync($assigneeIds);

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

        if (array_key_exists('status', $data)) {
            $this->ensureValidStatus((string) $data['status']);
        }
        if (array_key_exists('priority', $data)) {
            $this->ensureValidPriority((string) $data['priority']);
        }

        $assigneeIds = null;
        if (array_key_exists('assignee_ids', $data) || array_key_exists('assigned_to', $data)) {
            $requestedIds = (array) ($data['assignee_ids'] ?? []);
            if (! empty($data['assigned_to'])) {
                $requestedIds[] = (int) $data['assigned_to'];
            }
            $assigneeIds = $this->visibleAssigneeIds($actor, $requestedIds);
        }

        return DB::transaction(function () use ($actor, $task, $data, $assigneeIds): Task {
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
            if (array_key_exists('reminder_at', $data)) {
                $updateData['reminder_at'] = $data['reminder_at'];
                $updateData['reminder_sent_at'] = null; // Reset sent flag when reminder time changes
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

            if ($assigneeIds !== null) {
                $updatedTask->assignees()->sync($assigneeIds);
            }

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

    private function ensureValidStatus(string $status): void
    {
        if (TaskStatus::tryFrom($status) === null) {
            throw ValidationException::withMessages([
                'taskStatus' => 'Trạng thái công việc không hợp lệ.',
            ]);
        }
    }

    private function ensureValidPriority(string $priority): void
    {
        if (TaskPriority::tryFrom($priority) === null) {
            throw ValidationException::withMessages([
                'taskPriority' => 'Độ ưu tiên công việc không hợp lệ.',
            ]);
        }
    }

    /**
     * @param  array<int, int|string|null>  $requestedIds
     * @return list<int>
     */
    private function visibleAssigneeIds(User $actor, array $requestedIds): array
    {
        $ids = collect($requestedIds)
            ->filter(fn (mixed $id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        foreach ($ids as $id) {
            if ($this->users->findVisibleActiveUser($actor, $id) === null) {
                throw ValidationException::withMessages([
                    'assigneeIds' => 'Người được phân công không hoạt động hoặc nằm ngoài phạm vi dữ liệu của bạn.',
                ]);
            }
        }

        /** @var list<int> */
        return $ids->all();
    }
}
