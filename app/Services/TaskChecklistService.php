<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TaskChecklist;
use App\Models\User;
use App\Repositories\Contracts\TaskRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class TaskChecklistService
{
    public function __construct(
        private TaskRepository $tasks,
        private SystemAuditService $audit,
    ) {}

    public function addChecklistItem(User $actor, int $taskId, string $title): TaskChecklist
    {
        $task = $this->tasks->findVisibleForUserOrFail($actor, $taskId);
        Gate::forUser($actor)->authorize('update', $task);

        return DB::transaction(function () use ($actor, $task, $title): TaskChecklist {
            $maxPosition = (int) $task->checklists()->max('position');

            $item = TaskChecklist::query()->create([
                'task_id' => $task->id,
                'title' => trim($title),
                'is_completed' => false,
                'position' => $maxPosition + 1,
                'created_by' => $actor->id,
            ]);

            $this->audit->record(
                actor: $actor,
                subject: $task,
                event: 'checklist_added',
                description: "Thêm mục kiểm tra '{$item->title}' vào công việc '{$task->title}'",
                old: null,
                new: $item->toArray(),
            );

            return $item;
        });
    }

    public function toggleChecklistItem(User $actor, int $checklistItemId): TaskChecklist
    {
        /** @var TaskChecklist $item */
        $item = TaskChecklist::query()->with('task')->findOrFail($checklistItemId);
        $task = $this->tasks->findVisibleForUserOrFail($actor, $item->task_id);
        Gate::forUser($actor)->authorize('update', $task);

        return DB::transaction(function () use ($actor, $item, $task): TaskChecklist {
            $before = $item->toArray();
            $newStatus = ! $item->is_completed;

            $item->update([
                'is_completed' => $newStatus,
                'completed_at' => $newStatus ? now() : null,
            ]);

            $statusText = $newStatus ? 'hoàn thành' : 'chưa hoàn thành';
            $this->audit->record(
                actor: $actor,
                subject: $task,
                event: 'checklist_toggled',
                description: "Đánh dấu {$statusText} mục '{$item->title}' trong công việc '{$task->title}'",
                old: $before,
                new: $item->toArray(),
            );

            return $item;
        });
    }

    public function deleteChecklistItem(User $actor, int $checklistItemId): bool
    {
        /** @var TaskChecklist $item */
        $item = TaskChecklist::query()->with('task')->findOrFail($checklistItemId);
        $task = $this->tasks->findVisibleForUserOrFail($actor, $item->task_id);
        Gate::forUser($actor)->authorize('update', $task);

        return DB::transaction(function () use ($actor, $item, $task): bool {
            $before = $item->toArray();
            $result = (bool) $item->delete();

            $this->audit->record(
                actor: $actor,
                subject: $task,
                event: 'checklist_deleted',
                description: "Xóa mục kiểm tra '{$item->title}' khỏi công việc '{$task->title}'",
                old: $before,
                new: null,
            );

            return $result;
        });
    }
}
