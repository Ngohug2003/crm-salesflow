<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DepartmentOperationException;
use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepository;

final readonly class DepartmentService
{
    public function __construct(private DepartmentRepository $departments) {}

    /** @param array{name: string, code: string, description: ?string, parent_id: ?int, sort_order: int, is_active: bool} $attributes */
    public function save(?int $departmentId, array $attributes): Department
    {
        $department = $departmentId === null
            ? new Department
            : $this->departments->findWithParentOrFail($departmentId);

        $parentId = $attributes['parent_id'];

        if ($departmentId !== null && $parentId !== null) {
            $invalidParentIds = [$departmentId, ...$this->departments->descendantIds($departmentId)];

            if (in_array($parentId, $invalidParentIds, true)) {
                throw new DepartmentOperationException(
                    'parentId',
                    'Không thể chọn chính phòng ban hoặc phòng ban con làm cấp cha.',
                );
            }
        }

        if (! $attributes['is_active'] && $department->exists && $this->departments->hasActiveChildren($department)) {
            throw new DepartmentOperationException('isActive', 'Hãy ngừng hoạt động các phòng ban con trước.');
        }

        if ($attributes['is_active'] && $parentId !== null) {
            $parent = $this->departments->findOrFail($parentId);

            if (! $parent->is_active) {
                throw new DepartmentOperationException('parentId', 'Phòng ban cha phải hoạt động trước.');
            }
        }

        return $this->departments->save($department, $attributes);
    }

    public function toggleActive(int $departmentId): Department
    {
        $department = $this->departments->findWithParentOrFail($departmentId);

        if ($department->is_active && $this->departments->hasActiveChildren($department)) {
            throw new DepartmentOperationException('isActive', 'Hãy ngừng hoạt động các phòng ban con trước.');
        }

        if (! $department->is_active && $department->parent !== null && ! $department->parent->is_active) {
            throw new DepartmentOperationException('isActive', 'Phòng ban cha phải hoạt động trước khi bật phòng ban này.');
        }

        return $this->departments->save($department, [
            'name' => $department->name,
            'code' => $department->code,
            'description' => $department->description,
            'parent_id' => $department->parent_id,
            'sort_order' => $department->sort_order,
            'is_active' => ! $department->is_active,
        ]);
    }

    public function delete(int $departmentId): void
    {
        $department = $this->departments->findOrFail($departmentId);

        if ($this->departments->hasChildren($department)) {
            throw new DepartmentOperationException(
                'departmentId',
                'Không thể xóa phòng ban đang có phòng ban con. Hãy chuyển hoặc xóa các phòng ban con trước.',
            );
        }

        if ($this->departments->hasUsers($department)) {
            throw new DepartmentOperationException(
                'departmentId',
                'Không thể xóa phòng ban đang có người dùng. Hãy chuyển người dùng sang phòng ban khác trước.',
            );
        }

        $this->departments->delete($department);
    }
}
