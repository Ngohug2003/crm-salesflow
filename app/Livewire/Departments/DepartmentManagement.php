<?php

declare(strict_types=1);

namespace App\Livewire\Departments;

use App\Exceptions\DepartmentOperationException;
use App\Livewire\Forms\DepartmentForm;
use App\Models\Department;
use App\Models\User;
use App\Repositories\Contracts\DepartmentRepository;
use App\Services\DepartmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

final class DepartmentManagement extends Component
{
    public DepartmentForm $form;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public bool $showForm = false;

    public ?string $notice = null;

    public string $noticeType = 'success';

    public ?int $pendingDeleteId = null;

    public string $pendingDeleteName = '';

    public ?string $deleteError = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Department::class);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function departments(): Collection
    {
        Gate::authorize('viewAny', Department::class);

        return $this->repository()->search($this->search, $this->status);
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function parentOptions(): Collection
    {
        Gate::authorize('viewAny', Department::class);

        $excludedIds = $this->form->departmentId === null
            ? []
            : [$this->form->departmentId, ...$this->repository()->descendantIds($this->form->departmentId)];

        return $this->repository()->activeOptions($excludedIds);
    }

    /** @return array{total: int, active: int, inactive: int, roots: int} */
    #[Computed]
    public function stats(): array
    {
        Gate::authorize('viewAny', Department::class);

        return $this->repository()->stats();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', Department::class);

        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('modal-show', name: 'department-form');
    }

    public function openEdit(int $departmentId): void
    {
        $department = $this->repository()->findOrFail($departmentId);
        Gate::authorize('update', $department);

        $this->resetValidation();
        $this->notice = null;
        $this->form->fillFrom($department);
        $this->showForm = true;
        $this->dispatch('modal-show', name: 'department-form');
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('modal-close', name: 'department-form');
    }

    public function save(): void
    {
        if ($this->form->departmentId === null) {
            Gate::authorize('create', Department::class);
        } else {
            Gate::authorize('update', $this->repository()->findOrFail($this->form->departmentId));
        }

        try {
            $isCreating = $this->form->departmentId === null;
            $this->service()->save($this->currentUser(), $this->form->departmentId, $this->form->validatedPayload());
        } catch (DepartmentOperationException $exception) {
            $this->addError("form.{$exception->field}", $exception->getMessage());

            return;
        }

        $this->notice = $isCreating ? 'Đã tạo phòng ban mới.' : 'Đã cập nhật phòng ban.';
        $this->noticeType = 'success';
        $this->resetForm(keepNotice: true);
        $this->showForm = false;
        $this->dispatch('modal-close', name: 'department-form');
        $this->forgetComputedValues();
    }

    public function toggleActive(int $departmentId): void
    {
        Gate::authorize('update', $this->repository()->findOrFail($departmentId));

        try {
            $department = $this->service()->toggleActive($this->currentUser(), $departmentId);
        } catch (DepartmentOperationException $exception) {
            $this->notice = $exception->getMessage();
            $this->noticeType = 'error';

            return;
        }

        $this->notice = $department->is_active
            ? 'Đã bật hoạt động phòng ban.'
            : 'Đã ngừng hoạt động phòng ban.';
        $this->noticeType = 'success';
        $this->forgetComputedValues();
    }

    public function openDelete(int $departmentId): void
    {
        $department = $this->repository()->findOrFail($departmentId);
        Gate::authorize('delete', $department);

        $this->pendingDeleteId = $department->id;
        $this->pendingDeleteName = $department->name;
        $this->deleteError = null;
        $this->dispatch('modal-show', name: 'delete-department');
    }

    public function cancelDelete(): void
    {
        $this->resetDeleteConfirmation();
        $this->dispatch('modal-close', name: 'delete-department');
    }

    public function dismissDelete(): void
    {
        $this->resetDeleteConfirmation();
    }

    public function confirmDelete(): void
    {
        if ($this->pendingDeleteId === null) {
            return;
        }

        Gate::authorize('delete', $this->repository()->findOrFail($this->pendingDeleteId));

        try {
            $this->service()->delete($this->currentUser(), $this->pendingDeleteId);
        } catch (DepartmentOperationException $exception) {
            $this->deleteError = $exception->getMessage();

            return;
        }

        $this->notice = "Đã xóa phòng ban {$this->pendingDeleteName}.";
        $this->noticeType = 'success';
        $this->resetDeleteConfirmation();
        $this->dispatch('modal-close', name: 'delete-department');
        $this->forgetComputedValues();
    }

    public function updatedFormCode(string $value): void
    {
        $this->form->code = mb_strtoupper($value);
    }

    public function render(): View
    {
        return view('livewire.departments.department-management');
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

    private function forgetComputedValues(): void
    {
        unset($this->departments, $this->parentOptions, $this->stats);
    }

    private function resetDeleteConfirmation(): void
    {
        $this->pendingDeleteId = null;
        $this->pendingDeleteName = '';
        $this->deleteError = null;
    }

    private function repository(): DepartmentRepository
    {
        return app(DepartmentRepository::class);
    }

    private function service(): DepartmentService
    {
        return app(DepartmentService::class);
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
