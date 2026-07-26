<?php

declare(strict_types=1);

namespace App\Livewire\Tasks;

use App\Data\TaskFilterData;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\TaskManagementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class TaskList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $status = '';

    #[Url(as: 'priority', history: true)]
    public string $priority = '';

    #[Url(as: 'assigned', history: true)]
    public string $assignedTo = '';

    #[Url(as: 'sort', history: true)]
    public string $sortBy = 'due_date';

    #[Url(as: 'dir', history: true)]
    public string $sortDirection = 'asc';

    public int $perPage = 15;

    // State cho Modal Form (Tạo / Sửa Task)
    public bool $showModal = false;

    public ?int $editingTaskId = null;

    public string $title = '';

    public string $description = '';

    public string $taskStatus = 'todo';

    public string $taskPriority = 'medium';

    public ?string $dueDate = null;

    public ?string $reminderDate = null;

    public ?int $assigneeId = null;

    /** @var array<int, int> */
    public array $assigneeIds = [];

    public ?int $confirmingDeleteTaskId = null;

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('viewAny', Task::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'priority', 'assignedTo');
        $this->sortBy = 'due_date';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        /** @var User $actor */
        $actor = Auth::user();
        $this->assigneeId = $actor->id;
        $this->showModal = true;
    }

    public function openEditModal(int $taskId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);

        $task = $service->get($actor, $taskId);

        $this->editingTaskId = $task->id;
        $this->title = $task->title;
        $this->description = $task->description ?? '';
        $this->taskStatus = $task->status->value;
        $this->taskPriority = $task->priority->value;
        $this->dueDate = $task->due_date?->format('Y-m-d\TH:i') ?? null;
        $this->reminderDate = $task->reminder_at?->format('Y-m-d\TH:i') ?? null;
        $this->assigneeId = $task->assigned_to;
        $task->load('assignees');
        $this->assigneeIds = $task->assignees->pluck('id')->toArray();

        $this->showModal = true;
    }

    public function saveTask(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:50000'],
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

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'status' => $this->taskStatus,
            'priority' => $this->taskPriority,
            'due_date' => $this->dueDate !== '' ? $this->dueDate : null,
            'reminder_at' => $this->reminderDate !== '' ? $this->reminderDate : null,
            'assigned_to' => $this->assigneeId,
            'assignee_ids' => $this->assigneeIds,
        ];

        try {
            if ($this->editingTaskId !== null) {
                $service->update($actor, $this->editingTaskId, $data);
                session()->flash('message', 'Cập nhật công việc thành công.');
            } else {
                $service->create($actor, $data);
                session()->flash('message', 'Tạo công việc thành công.');
            }

            $this->showModal = false;
            $this->resetForm();
        } catch (\Throwable $e) {
            $this->addError('task_error', $e->getMessage());
        }
    }

    public function toggleTaskStatus(int $taskId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);

        try {
            $service->toggleStatus($actor, $taskId);
        } catch (\Throwable $e) {
            $this->addError('task_error', $e->getMessage());
        }
    }

    public function confirmDeleteTask(int $taskId): void
    {
        $this->confirmingDeleteTaskId = $taskId;
    }

    public function deleteConfirmedTask(): void
    {
        if ($this->confirmingDeleteTaskId !== null) {
            /** @var User $actor */
            $actor = Auth::user();
            /** @var TaskManagementService $service */
            $service = app(TaskManagementService::class);

            try {
                $service->delete($actor, $this->confirmingDeleteTaskId);
                session()->flash('message', 'Đã xóa công việc thành công.');
            } catch (\Throwable $e) {
                $this->addError('task_error', $e->getMessage());
            }

            $this->confirmingDeleteTaskId = null;
        }
    }

    /** @return LengthAwarePaginator<int, Task> */
    #[Computed]
    public function tasks(): LengthAwarePaginator
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);

        $filters = new TaskFilterData(
            search: $this->search !== '' ? $this->search : null,
            status: $this->status !== '' ? $this->status : null,
            priority: $this->priority !== '' ? $this->priority : null,
            assignedTo: $this->assignedTo !== '' ? (int) $this->assignedTo : null,
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );

        return $service->list($actor, $filters, $this->perPage);
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        /** @var User $actor */
        $actor = Auth::user();

        return app(UserRepository::class)->visibleActiveUsers($actor);
    }

    private function resetForm(): void
    {
        $this->editingTaskId = null;
        $this->title = '';
        $this->description = '';
        $this->taskStatus = 'todo';
        $this->taskPriority = 'medium';
        $this->dueDate = null;
        $this->reminderDate = null;
        $this->assigneeId = null;
        $this->assigneeIds = [];
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.tasks.task-list');
    }
}
