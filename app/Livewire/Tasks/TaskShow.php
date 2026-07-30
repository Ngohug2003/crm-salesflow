<?php

declare(strict_types=1);

namespace App\Livewire\Tasks;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\TaskChecklistService;
use App\Services\TaskCommentService;
use App\Services\TaskManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class TaskShow extends Component
{
    public int $taskId;

    public ?Task $task = null;

    public string $newChecklistTitle = '';

    public string $newCommentContent = '';

    public ?int $replyToCommentId = null;

    public ?string $replyToUserName = null;

    public ?string $replyToContentPreview = null;

    public string $taskStatus = 'todo';

    public string $taskPriority = 'medium';

    public ?string $dueDate = null;

    public ?string $reminderDate = null;

    public ?int $assigneeId = null;

    /** @var array<int, int> */
    public array $assigneeIds = [];

    public bool $canEdit = false;

    public function mount(int $taskId): void
    {
        $this->taskId = $taskId;
        $this->loadTask();
    }

    public function loadTask(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);

        $this->task = $service->get($actor, $this->taskId);
        $this->task->load(['checklists.creator', 'comments.user', 'comments.parent.user', 'attachments.createdBy', 'assignee', 'assignees', 'creator', 'subject']);

        $this->canEdit = $actor->can('update', $this->task);
        $this->taskStatus = $this->task->status->value;
        $this->taskPriority = $this->task->priority->value;
        $this->dueDate = $this->task->due_date?->format('Y-m-d\TH:i');
        $this->reminderDate = $this->task->reminder_at?->format('Y-m-d\TH:i');
        $this->assigneeId = $this->task->assigned_to;
        $this->assigneeIds = $this->task->assignees->pluck('id')->toArray();
    }

    public function updateTaskSettings(): void
    {
        $this->validate([
            'taskStatus' => ['required', Rule::in(['todo', 'in_progress', 'completed', 'cancelled'])],
            'taskPriority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'dueDate' => ['nullable', 'date'],
            'reminderDate' => ['nullable', 'date'],
            'assigneeId' => ['nullable', 'integer', 'exists:users,id'],
            'assigneeIds' => ['array'],
            'assigneeIds.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);

        try {
            $service->update($actor, $this->taskId, [
                'status' => $this->taskStatus,
                'priority' => $this->taskPriority,
                'due_date' => $this->dueDate !== '' ? $this->dueDate : null,
                'reminder_at' => $this->reminderDate !== '' ? $this->reminderDate : null,
                'assigned_to' => $this->assigneeId,
                'assignee_ids' => $this->assigneeIds,
            ]);

            session()->flash('message', 'Đã cập nhật thông tin công việc.');
            $this->loadTask();
        } catch (\Throwable $e) {
            $this->addError('task_error', $e->getMessage());
        }
    }

    public function updatedAssigneeId(): void
    {
        $this->updateTaskSettings();
    }

    public function addChecklistItem(): void
    {
        $this->validate(['newChecklistTitle' => ['required', 'string', 'max:255']]);

        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskChecklistService $service */
        $service = app(TaskChecklistService::class);

        try {
            $service->addChecklistItem($actor, $this->taskId, $this->newChecklistTitle);
            $this->newChecklistTitle = '';
            $this->loadTask();
        } catch (\Throwable $e) {
            $this->addError('checklist_error', $e->getMessage());
        }
    }

    public function toggleChecklistItem(int $checklistItemId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskChecklistService $service */
        $service = app(TaskChecklistService::class);

        try {
            $service->toggleChecklistItem($actor, $checklistItemId);
            $this->loadTask();
        } catch (\Throwable $e) {
            $this->addError('checklist_error', $e->getMessage());
        }
    }

    public function deleteChecklistItem(int $checklistItemId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskChecklistService $service */
        $service = app(TaskChecklistService::class);

        try {
            $service->deleteChecklistItem($actor, $checklistItemId);
            $this->loadTask();
        } catch (\Throwable $e) {
            $this->addError('checklist_error', $e->getMessage());
        }
    }

    public function setReplyTo(int $commentId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);
        $service->get($actor, $this->taskId);

        /** @var TaskComment $comment */
        $comment = TaskComment::query()
            ->with('user')
            ->where('task_id', $this->taskId)
            ->findOrFail($commentId);
        $userName = $comment->user->name;

        $this->replyToCommentId = $commentId;
        $this->replyToUserName = $userName;
        $this->replyToContentPreview = mb_strimwidth($comment->content, 0, 60, '...');
        $this->newCommentContent = '@'.$userName.' ';
    }

    public function cancelReply(): void
    {
        $this->replyToCommentId = null;
        $this->replyToUserName = null;
        $this->replyToContentPreview = null;
    }

    public function addComment(): void
    {
        $this->validate(['newCommentContent' => ['required', 'string', 'max:5000']]);

        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskCommentService $service */
        $service = app(TaskCommentService::class);

        try {
            $service->addComment($actor, $this->taskId, $this->newCommentContent, $this->replyToCommentId);
            $this->newCommentContent = '';
            $this->cancelReply();
            $this->loadTask();
        } catch (\Throwable $e) {
            $this->addError('comment_error', $e->getMessage());
        }
    }

    public function deleteComment(int $commentId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskCommentService $service */
        $service = app(TaskCommentService::class);

        try {
            $service->deleteComment($actor, $commentId);
            $this->loadTask();
        } catch (\Throwable $e) {
            $this->addError('comment_error', $e->getMessage());
        }
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        /** @var User $actor */
        $actor = Auth::user();
        $visibleUsers = app(UserRepository::class)->visibleActiveUsers($actor);

        if ($this->task === null) {
            return $visibleUsers;
        }

        $this->task->loadMissing(['assignee', 'assignees', 'creator']);

        return $visibleUsers
            ->merge(collect([$this->task->assignee, $this->task->creator])->filter())
            ->merge($this->task->assignees)
            ->filter(fn (User $user): bool => $user->is_active !== false)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    public function render(): View
    {
        return view('livewire.tasks.task-show');
    }
}
