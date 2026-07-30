<?php

declare(strict_types=1);

namespace App\Livewire\Tasks;

use App\Data\CompanyFilterData;
use App\Data\ContactFilterData;
use App\Data\LeadFilterData;
use App\Data\OpportunityFilterData;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\CompanyManagementService;
use App\Services\ContactManagementService;
use App\Services\LeadDirectoryService;
use App\Services\OpportunityManagementService;
use App\Services\TaskManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class TaskCreate extends Component
{
    public string $title = '';

    public string $description = '';

    public string $taskStatus = 'todo';

    public string $taskPriority = 'medium';

    public ?string $dueDate = null;

    public ?string $reminderDate = null;

    public ?int $assigneeId = null;

    /** @var array<int, int> */
    public array $assigneeIds = [];

    #[Url(as: 'subject_type')]
    public ?string $subjectType = null;

    #[Url(as: 'subject_id')]
    public ?int $subjectId = null;

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('create', Task::class);

        $this->assigneeId = $actor->id;
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
            'subjectType' => ['nullable', Rule::in([
                Opportunity::class,
                Company::class,
                Lead::class,
                Contact::class,
            ])],
            'subjectId' => ['nullable', 'integer', 'min:1', 'required_with:subjectType'],
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
            'subject_type' => $this->subjectType !== '' ? $this->subjectType : null,
            'subject_id' => $this->subjectId !== null && $this->subjectId > 0 ? $this->subjectId : null,
        ];

        try {
            $task = $service->create($actor, $data);
            session()->flash('message', "Tạo công việc '{$task->title}' thành công.");

            $this->redirect(route('tasks.show', $task->id), navigate: true);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('task_error', 'Không thể tạo công việc. Vui lòng thử lại.');
        }
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        /** @var User $actor */
        $actor = Auth::user();

        return app(UserRepository::class)->visibleActiveUsers($actor);
    }

    /** @return Collection<int, Opportunity> */
    #[Computed]
    public function opportunities(): Collection
    {
        /** @var User $actor */
        $actor = Auth::user();

        $paginator = app(OpportunityManagementService::class)
            ->list($actor, new OpportunityFilterData(sortBy: 'title', sortDirection: 'asc'), 100);

        return collect($paginator->items());
    }

    /** @return Collection<int, Company> */
    #[Computed]
    public function companies(): Collection
    {
        /** @var User $actor */
        $actor = Auth::user();

        $paginator = app(CompanyManagementService::class)
            ->list($actor, new CompanyFilterData(sortBy: 'name', sortDirection: 'asc'), 100);

        return collect($paginator->items());
    }

    /** @return Collection<int, Lead> */
    #[Computed]
    public function leads(): Collection
    {
        /** @var User $actor */
        $actor = Auth::user();

        $paginator = app(LeadDirectoryService::class)
            ->paginate($actor, new LeadFilterData(sortBy: 'full_name', sortDirection: 'asc'), 100);

        return collect($paginator->items());
    }

    /** @return Collection<int, Contact> */
    #[Computed]
    public function contacts(): Collection
    {
        /** @var User $actor */
        $actor = Auth::user();

        $paginator = app(ContactManagementService::class)
            ->list($actor, new ContactFilterData(sortBy: 'full_name', sortDirection: 'asc'), 100);

        return collect($paginator->items());
    }

    public function render(): View
    {
        return view('livewire.tasks.task-create');
    }
}
