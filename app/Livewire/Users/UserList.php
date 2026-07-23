<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Data\UserListFilters;
use App\Exceptions\UserOperationException;
use App\Livewire\Forms\UserForm;
use App\Models\Department;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\UserDirectoryService;
use App\Services\UserManagementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class UserList extends Component
{
    use WithPagination;

    public UserForm $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $department = 'all';

    #[Url(except: 'all')]
    public string $role = 'all';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showForm = false;

    public ?string $notice = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', User::class);

        return $this->service()->paginate(
            $this->currentUser(),
            new UserListFilters($this->search, $this->department, $this->role, $this->status),
        );
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function departmentOptions(): Collection
    {
        return $this->service()->departmentOptions($this->currentUser());
    }

    /** @return array<string, string> */
    #[Computed]
    public function roleOptions(): array
    {
        return $this->service()->roleOptions();
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function formDepartmentOptions(): Collection
    {
        return $this->service()->formDepartmentOptions(
            $this->currentUser(),
            $this->form->originalDepartmentId,
        );
    }

    /** @return array<string, array{label: string, description: string, scope: string}> */
    #[Computed]
    public function roleAssignmentOptions(): array
    {
        return $this->service()->roleAssignmentOptions($this->currentUser());
    }

    public function openCreate(): void
    {
        Gate::authorize('create', User::class);

        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('modal-show', name: 'user-form');
    }

    public function openEdit(int $userId): void
    {
        $user = $this->repository()->findVisibleOrFail($this->currentUser(), $userId);
        Gate::authorize('update', $user);

        $this->resetValidation();
        $this->notice = null;
        $this->form->fillFrom($user);
        $this->showForm = true;
        $this->dispatch('modal-show', name: 'user-form');
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('modal-close', name: 'user-form');
    }

    public function save(): void
    {
        $actor = $this->currentUser();
        $user = $this->form->userId === null
            ? null
            : $this->repository()->findVisibleOrFail($actor, $this->form->userId);

        if ($user === null) {
            Gate::authorize('create', User::class);
        } else {
            Gate::authorize('update', $user);
        }

        $isCreating = $user === null;

        try {
            $savedUser = $this->managementService()->save($actor, $user, $this->form->validatedPayload());
        } catch (UserOperationException $exception) {
            $this->addError("form.{$exception->field}", $exception->getMessage());

            return;
        }

        $this->notice = $isCreating
            ? "Đã tạo người dùng {$savedUser->name}."
            : "Đã cập nhật người dùng {$savedUser->name}.";
        $this->resetForm(keepNotice: true);
        $this->showForm = false;
        $this->dispatch('modal-close', name: 'user-form');
        unset($this->users);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartment(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->department = 'all';
        $this->role = 'all';
        $this->status = 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.users.user-list');
    }

    private function service(): UserDirectoryService
    {
        return app(UserDirectoryService::class);
    }

    private function managementService(): UserManagementService
    {
        return app(UserManagementService::class);
    }

    private function repository(): UserRepository
    {
        return app(UserRepository::class);
    }

    private function resetForm(bool $keepNotice = false): void
    {
        $this->resetValidation();
        $this->form->clear();
        $this->showForm = false;

        if (! $keepNotice) {
            $this->notice = null;
        }
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
