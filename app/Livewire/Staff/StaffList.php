<?php

declare(strict_types=1);

namespace App\Livewire\Staff;

use App\Models\Department;
use App\Models\Province;
use App\Models\Staff;
use App\Models\Ward;
use App\Services\StaffService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

final class StaffList extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $filterDepartmentId = null;

    public string $filterActive = '';

    // Modal state for Staff Form
    public bool $showForm = false;

    public ?int $editingStaffId = null;

    public string $staffCode = '';

    public string $fullName = '';

    public string $email = '';

    public string $phone = '';

    public string $birthday = '';

    public string $address = '';

    public ?int $provinceId = null;

    public ?int $wardId = null;

    public ?int $departmentId = null;

    public string $position = '';

    public string $joinDate = '';

    public bool $isActive = true;

    public string $notes = '';

    // Modal state for Invite
    public bool $showInviteModal = false;

    public string $inviteName = '';

    public string $inviteEmail = '';

    public ?int $inviteDepartmentId = null;

    public string $inviteRole = 'sales';

    public function updatedProvinceId(): void
    {
        $this->wardId = null;
    }

    public function updatedDepartmentId(StaffService $staffService): void
    {
        $this->staffCode = $staffService->generateStaffCode($this->departmentId);
    }

    public function openCreate(StaffService $staffService): void
    {
        $this->resetForm();
        $this->staffCode = $staffService->generateStaffCode($this->departmentId);
        $this->showForm = true;
    }

    public function editStaff(int $staffId): void
    {
        $staff = Staff::query()->findOrFail($staffId);
        $this->editingStaffId = $staff->id;
        $this->staffCode = $staff->staff_code;
        $this->fullName = $staff->full_name;
        $this->email = $staff->email;
        $this->phone = $staff->phone ?? '';
        $this->birthday = $staff->birthday ? $staff->birthday->format('Y-m-d') : '';
        $this->address = $staff->address ?? '';
        $this->provinceId = $staff->province_id;
        $this->wardId = $staff->ward_id;
        $this->departmentId = $staff->department_id;
        $this->position = $staff->position ?? '';
        $this->joinDate = $staff->join_date ? $staff->join_date->format('Y-m-d') : '';
        $this->isActive = $staff->is_active;
        $this->notes = $staff->notes ?? '';

        $this->showForm = true;
    }

    public function saveStaff(StaffService $staffService): void
    {
        $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birthday' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'provinceId' => ['nullable', 'integer', 'exists:provinces,id'],
            'wardId' => ['nullable', 'integer', 'exists:wards,id'],
            'departmentId' => ['nullable', 'integer', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:100'],
            'joinDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $staffService->saveStaff([
            'staff_code' => $this->staffCode,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'birthday' => $this->birthday,
            'address' => $this->address,
            'province_id' => $this->provinceId,
            'ward_id' => $this->wardId,
            'department_id' => $this->departmentId,
            'position' => $this->position,
            'join_date' => $this->joinDate,
            'is_active' => $this->isActive,
            'notes' => $this->notes,
        ], $this->editingStaffId);

        $this->showForm = false;
        $this->resetForm();
        session()->flash('success', 'Đã lưu hồ sơ Nhân viên thành công.');
    }

    public function openInviteModal(): void
    {
        $this->inviteName = '';
        $this->inviteEmail = '';
        $this->inviteDepartmentId = null;
        $this->inviteRole = 'sales';
        $this->showInviteModal = true;
    }

    public function sendInvitation(StaffService $staffService): void
    {
        $this->validate([
            'inviteName' => ['required', 'string', 'max:255'],
            'inviteEmail' => ['required', 'email', 'max:255'],
            'inviteDepartmentId' => ['nullable', 'integer', 'exists:departments,id'],
            'inviteRole' => ['required', 'string'],
        ]);

        $user = Auth::user();
        if ($user !== null) {
            $staffService->inviteStaff(
                $this->inviteName,
                $this->inviteEmail,
                $this->inviteDepartmentId,
                $this->inviteRole,
                $user
            );
        }

        $this->showInviteModal = false;
        session()->flash('success', "Đã gửi email mời và tạo hồ sơ Nhân viên cho {$this->inviteEmail}.");
    }

    public function deleteStaff(int $staffId): void
    {
        Staff::query()->where('id', $staffId)->delete();
        session()->flash('success', 'Đã xóa hồ sơ Nhân viên.');
    }

    public function render(): View
    {
        $query = Staff::query()
            ->with(['department', 'provinceUnit', 'ward', 'user'])
            ->orderBy('created_at', 'desc');

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('staff_code', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterDepartmentId !== null) {
            $query->where('department_id', $this->filterDepartmentId);
        }

        if ($this->filterActive !== '') {
            $query->where('is_active', $this->filterActive === '1');
        }

        $staffList = $query->paginate(15);
        $departments = Department::query()->orderBy('name')->get();
        $provinces = Province::query()->where('is_active', true)->orderBy('name')->get();

        $wards = $this->provinceId !== null
            ? Ward::query()->where('province_id', $this->provinceId)->orderBy('name')->get()
            : collect();

        return view('livewire.staff.staff-list', [
            'staffList' => $staffList,
            'departments' => $departments,
            'provinces' => $provinces,
            'wards' => $wards,
        ])->layout('layouts.app', ['title' => 'Quản lý Nhân viên']);
    }

    private function resetForm(): void
    {
        $this->editingStaffId = null;
        $this->staffCode = '';
        $this->fullName = '';
        $this->email = '';
        $this->phone = '';
        $this->birthday = '';
        $this->address = '';
        $this->provinceId = null;
        $this->wardId = null;
        $this->departmentId = null;
        $this->position = '';
        $this->joinDate = '';
        $this->isActive = true;
        $this->notes = '';
    }
}
