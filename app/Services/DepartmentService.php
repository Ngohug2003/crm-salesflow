<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DepartmentOperationException;
use App\Models\Department;
use App\Models\User;
use App\Repositories\Contracts\DepartmentRepository;
use Illuminate\Support\Facades\DB;

final readonly class DepartmentService
{
    public function __construct(
        private DepartmentRepository $departments,
        private SystemAuditService $audit,
    ) {}

    /** @param array{name: string, code: string, description: ?string, parent_id: ?int, sort_order: int, is_active: bool} $attributes */
    public function save(User $actor, ?int $departmentId, array $attributes): Department
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

        return DB::transaction(function () use ($actor, $department, $attributes): Department {
            $old = $department->exists ? $this->snapshot($department) : null;
            $saved = $this->departments->save($department, $attributes);
            $this->audit->record(
                $actor,
                $saved,
                $old === null ? 'created' : 'updated',
                $old === null ? 'Tạo phòng ban' : 'Cập nhật phòng ban',
                $old,
                $this->snapshot($saved),
            );

            return $saved;
        });
    }

    public function toggleActive(User $actor, int $departmentId): Department
    {
        $department = $this->departments->findWithParentOrFail($departmentId);

        if ($department->is_active && $this->departments->hasActiveChildren($department)) {
            throw new DepartmentOperationException('isActive', 'Hãy ngừng hoạt động các phòng ban con trước.');
        }

        if (! $department->is_active && $department->parent !== null && ! $department->parent->is_active) {
            throw new DepartmentOperationException('isActive', 'Phòng ban cha phải hoạt động trước khi bật phòng ban này.');
        }

        return $this->save($actor, $departmentId, [
            'name' => $department->name,
            'code' => $department->code,
            'description' => $department->description,
            'parent_id' => $department->parent_id,
            'sort_order' => $department->sort_order,
            'is_active' => ! $department->is_active,
        ]);
    }

    public function delete(User $actor, int $departmentId): void
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

        DB::transaction(function () use ($actor, $department): void {
            $this->audit->record(
                $actor,
                $department,
                'deleted',
                'Xóa phòng ban',
                $this->snapshot($department),
                null,
            );
            $this->departments->delete($department);
        });
    }

    /** @return array{id: int, name: string, code: string, description: ?string, parent_id: ?int, sort_order: int, is_active: bool} */
    private function snapshot(Department $department): array
    {
        return [
            'id' => $department->getKey(),
            'name' => $department->name,
            'code' => $department->code,
            'description' => $department->description,
            'parent_id' => $department->parent_id,
            'sort_order' => $department->sort_order,
            'is_active' => $department->is_active,
        ];
    }
}
