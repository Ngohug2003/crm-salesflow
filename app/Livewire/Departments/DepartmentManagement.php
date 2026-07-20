<?php

declare(strict_types=1);

namespace App\Livewire\Departments;

use App\Exceptions\DepartmentOperationException;
use App\Livewire\Forms\DepartmentForm;
use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepository;
use App\Services\DepartmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
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

    /** @return Collection<int, Department> */
    #[Computed]
    public function departments(): Collection
    {
        return $this->repository()->search($this->search, $this->status);
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function parentOptions(): Collection
    {
        $excludedIds = $this->form->departmentId === null
            ? []
            : [$this->form->departmentId, ...$this->repository()->descendantIds($this->form->departmentId)];

        return $this->repository()->activeOptions($excludedIds);
    }

    /** @return array{total: int, active: int, inactive: int, roots: int} */
    #[Computed]
    public function stats(): array
    {
        return $this->repository()->stats();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $departmentId): void
    {
        $this->resetValidation();
        $this->notice = null;
        $this->form->fillFrom($this->repository()->findOrFail($departmentId));
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        try {
            $isCreating = $this->form->departmentId === null;
            $this->service()->save($this->form->departmentId, $this->form->validatedPayload());
        } catch (DepartmentOperationException $exception) {
            $this->addError("form.{$exception->field}", $exception->getMessage());

            return;
        }

        $this->notice = $isCreating ? 'Đã tạo phòng ban mới.' : 'Đã cập nhật phòng ban.';
        $this->noticeType = 'success';
        $this->resetForm(keepNotice: true);
        $this->forgetComputedValues();
    }

    public function toggleActive(int $departmentId): void
    {
        try {
            $department = $this->service()->toggleActive($departmentId);
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

        try {
            $this->service()->delete($this->pendingDeleteId);
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
}
