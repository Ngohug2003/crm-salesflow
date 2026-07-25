<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TaskComment;
use App\Models\User;
use App\Repositories\Contracts\TaskRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class TaskCommentService
{
    public function __construct(
        private TaskRepository $tasks,
        private SystemAuditService $audit,
    ) {}

    public function addComment(User $actor, int $taskId, string $content): TaskComment
    {
        $task = $this->tasks->findVisibleForUserOrFail($actor, $taskId);
        Gate::forUser($actor)->authorize('view', $task);

        return DB::transaction(function () use ($actor, $task, $content): TaskComment {
            $comment = TaskComment::query()->create([
                'task_id' => $task->id,
                'user_id' => $actor->id,
                'content' => trim($content),
            ]);

            $this->audit->record(
                actor: $actor,
                subject: $task,
                event: 'comment_added',
                description: "Đăng bình luận trên công việc '{$task->title}'",
                old: null,
                new: $comment->toArray(),
            );

            return $comment;
        });
    }

    public function deleteComment(User $actor, int $commentId): bool
    {
        /** @var TaskComment $comment */
        $comment = TaskComment::query()->with('task')->findOrFail($commentId);
        $task = $this->tasks->findVisibleForUserOrFail($actor, $comment->task_id);

        if ($comment->user_id !== $actor->id) {
            Gate::forUser($actor)->authorize('update', $task);
        }

        return DB::transaction(function () use ($actor, $comment, $task): bool {
            $before = $comment->toArray();
            $result = (bool) $comment->delete();

            $this->audit->record(
                actor: $actor,
                subject: $task,
                event: 'comment_deleted',
                description: "Xóa bình luận trên công việc '{$task->title}'",
                old: $before,
                new: null,
            );

            return $result;
        });
    }
}
