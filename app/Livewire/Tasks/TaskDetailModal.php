<?php

declare(strict_types=1);

namespace App\Livewire\Tasks;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskChecklistService;
use App\Services\TaskCommentService;
use App\Services\TaskManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

final class TaskDetailModal extends Component
{
    public bool $showModal = false;

    public ?int $taskId = null;

    public ?Task $task = null;

    public string $newChecklistTitle = '';

    public string $newCommentContent = '';

    #[On('open-task-detail')]
    public function openModal(int $taskId): void
    {
        $this->taskId = $taskId;
        $this->loadTask();
        $this->showModal = true;
    }

    public function loadTask(): void
    {
        if ($this->taskId === null) {
            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);

        $this->task = $service->get($actor, $this->taskId);
        $this->task->load(['checklists.creator', 'comments.user', 'attachments.createdBy', 'assignee', 'creator']);
    }

    public function addChecklistItem(): void
    {
        $this->validate([
            'newChecklistTitle' => ['required', 'string', 'max:255'],
        ]);

        if ($this->taskId === null) {
            return;
        }

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

    public function addComment(): void
    {
        $this->validate([
            'newCommentContent' => ['required', 'string'],
        ]);

        if ($this->taskId === null) {
            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskCommentService $service */
        $service = app(TaskCommentService::class);

        try {
            $service->addComment($actor, $this->taskId, $this->newCommentContent);
            $this->newCommentContent = '';
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

    public function render(): View
    {
        return view('livewire.tasks.task-detail-modal');
    }
}
