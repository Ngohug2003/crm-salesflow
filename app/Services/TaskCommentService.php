<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskMentionNotification;
use App\Repositories\Contracts\TaskRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class TaskCommentService
{
    public function __construct(
        private TaskRepository $tasks,
        private UserRepository $users,
        private SystemAuditService $audit,
    ) {}

    public function addComment(User $actor, int $taskId, string $content, ?int $parentId = null): TaskComment
    {
        $task = $this->tasks->findVisibleForUserOrFail($actor, $taskId);
        Gate::forUser($actor)->authorize('view', $task);

        if ($parentId !== null) {
            TaskComment::query()
                ->where('task_id', $task->id)
                ->findOrFail($parentId);
        }

        $comment = DB::transaction(function () use ($actor, $task, $content, $parentId): TaskComment {
            $comment = TaskComment::query()->create([
                'task_id' => $task->id,
                'parent_id' => $parentId,
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

        $task->loadMissing(['assignee', 'assignees', 'creator']);
        $mentionCandidates = $this->users->visibleActiveUsers($actor)
            ->merge(collect([$task->assignee, $task->creator])->filter())
            ->merge($task->assignees)
            ->filter(fn (User $user): bool => $user->is_active && $user->getKey() !== $actor->getKey())
            ->unique('id');

        foreach ($mentionCandidates as $recipient) {
            $isMentioned = str_contains($content, '@'.$recipient->name)
                || str_contains($content, '@'.$recipient->email);

            if ($isMentioned && Gate::forUser($recipient)->allows('view', $task)) {
                $recipient->notify(new TaskMentionNotification($task, $actor, $content));
            }
        }

        return $comment;
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
