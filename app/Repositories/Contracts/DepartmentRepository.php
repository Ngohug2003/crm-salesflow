<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepository
{
    /** @return Collection<int, Department> */
    public function search(string $term, string $status): Collection;

    /**
     * @param  list<int>  $excludedIds
     * @return Collection<int, Department>
     */
    public function activeOptions(array $excludedIds = []): Collection;

    /** @return array{total: int, active: int, inactive: int, roots: int} */
    public function stats(): array;

    public function findOrFail(int $departmentId): Department;

    public function findWithParentOrFail(int $departmentId): Department;

    /** @return list<int> */
    public function descendantIds(int $departmentId): array;

    public function hasActiveChildren(Department $department): bool;

    public function hasChildren(Department $department): bool;

    public function hasUsers(Department $department): bool;

    /** @param array{name: string, code: string, description: ?string, parent_id: ?int, sort_order: int, is_active: bool} $attributes */
    public function save(Department $department, array $attributes): Department;

    public function delete(Department $department): void;
}
