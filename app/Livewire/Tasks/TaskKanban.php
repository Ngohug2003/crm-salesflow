<?php

declare(strict_types=1);

namespace App\Livewire\Tasks;

use App\Data\TaskFilterData;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\TaskManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class TaskKanban extends Component
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'priority', history: true)]
    public string $priority = '';

    #[Url(as: 'assigned', history: true)]
    public string $assignedTo = '';

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('viewAny', Task::class);
    }

    public function moveTask(int $taskId, string $targetStatus): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);

        try {
            $service->updateStatus($actor, $taskId, $targetStatus);
        } catch (\Throwable $e) {
            $this->addError('kanban_error', $e->getMessage());
        }
    }

    /** @return array<string, Collection<int, Task>> */
    #[Computed]
    public function columns(): array
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);

        $filters = new TaskFilterData(
            search: $this->search !== '' ? $this->search : null,
            priority: $this->priority !== '' ? $this->priority : null,
            assignedTo: $this->assignedTo !== '' ? (int) $this->assignedTo : null,
            sortBy: 'created_at',
            sortDirection: 'desc',
        );

        $allTasks = $service->list($actor, $filters, 100);

        $todo = new Collection;
        $inProgress = new Collection;
        $completed = new Collection;
        $cancelled = new Collection;

        foreach ($allTasks->items() as $task) {
            match ($task->status) {
                TaskStatus::Todo => $todo->push($task),
                TaskStatus::InProgress => $inProgress->push($task),
                TaskStatus::Completed => $completed->push($task),
                TaskStatus::Cancelled => $cancelled->push($task),
            };
        }

        return [
            'todo' => $todo,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'cancelled' => $cancelled,
        ];
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        /** @var User $actor */
        $actor = Auth::user();

        return app(UserRepository::class)->visibleActiveUsers($actor);
    }

    public function render(): View
    {
        return view('livewire.tasks.task-kanban');
    }
}
