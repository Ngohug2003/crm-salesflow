<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\Department;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class DepartmentForm extends Form
{
    public ?int $departmentId = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public string $parentId = '';

    public int $sortOrder = 0;

    public bool $isActive = true;

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/',
                Rule::unique('departments', 'code')->ignore($this->departmentId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'parentId' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:32767'],
            'isActive' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'code.regex' => 'Mã phòng ban chỉ gồm chữ in hoa, số và dấu gạch ngang.',
            'code.unique' => 'Mã phòng ban đã tồn tại.',
            'parentId.exists' => 'Phòng ban cha không tồn tại hoặc đã ngừng hoạt động.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'tên phòng ban',
            'code' => 'mã phòng ban',
            'description' => 'mô tả',
            'parentId' => 'phòng ban cha',
            'sortOrder' => 'thứ tự',
            'isActive' => 'trạng thái',
        ];
    }

    public function fillFrom(Department $department): void
    {
        $this->departmentId = $department->getKey();
        $this->name = $department->name;
        $this->code = $department->code;
        $this->description = $department->description ?? '';
        $this->parentId = $department->parent_id === null ? '' : (string) $department->parent_id;
        $this->sortOrder = $department->sort_order;
        $this->isActive = $department->is_active;
    }

    /** @return array{name: string, code: string, description: ?string, parent_id: ?int, sort_order: int, is_active: bool} */
    public function validatedPayload(): array
    {
        $this->name = trim($this->name);
        $this->code = mb_strtoupper(trim($this->code));
        $this->description = trim($this->description);

        $validated = $this->validate();

        return [
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] === '' ? null : $validated['description'],
            'parent_id' => $validated['parentId'] === '' ? null : (int) $validated['parentId'],
            'sort_order' => $validated['sortOrder'],
            'is_active' => $validated['isActive'],
        ];
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetValidation();
        $this->isActive = true;
    }
}
