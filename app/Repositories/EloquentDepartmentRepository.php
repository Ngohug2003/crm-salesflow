<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class EloquentDepartmentRepository implements DepartmentRepository
{
    public function search(string $term, string $status): Collection
    {
        $term = mb_strtolower(trim($term));

        return Department::query()
            ->with('parent:id,name')
            ->withCount('users')
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = "%{$term}%";

                $query->where(function (Builder $query) use ($like): void {
                    $query->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(code) LIKE ?', [$like]);
                });
            })
            ->when($status === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function activeOptions(array $excludedIds = []): Collection
    {
        return Department::query()
            ->active()
            ->when($excludedIds !== [], fn (Builder $query): Builder => $query->whereNotIn('id', $excludedIds))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function userFilterOptions(?array $onlyIds = null): Collection
    {
        return Department::query()
            ->when($onlyIds !== null, fn (Builder $query): Builder => $query->whereIn('id', $onlyIds))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function stats(): array
    {
        return [
            'total' => Department::query()->count(),
            'active' => Department::query()->where('is_active', true)->count(),
            'inactive' => Department::query()->where('is_active', false)->count(),
            'roots' => Department::query()->whereNull('parent_id')->count(),
        ];
    }

    public function findOrFail(int $departmentId): Department
    {
        return Department::query()->findOrFail($departmentId);
    }

    public function findWithParentOrFail(int $departmentId): Department
    {
        return Department::query()->with('parent')->findOrFail($departmentId);
    }

    public function descendantIds(int $departmentId): array
    {
        $descendantIds = [];
        $parentIds = [$departmentId];

        while ($parentIds !== []) {
            $childIds = Department::query()
                ->whereIn('parent_id', $parentIds)
                ->pluck('id')
                ->map(fn (int|string $id): int => (int) $id)
                ->all();

            $newIds = array_values(array_diff($childIds, $descendantIds));
            $descendantIds = [...$descendantIds, ...$newIds];
            $parentIds = $newIds;
        }

        return $descendantIds;
    }

    public function hasActiveChildren(Department $department): bool
    {
        return $department->children()->active()->exists();
    }

    public function hasChildren(Department $department): bool
    {
        return $department->children()->exists();
    }

    public function hasUsers(Department $department): bool
    {
        return $department->users()->exists();
    }

    public function save(Department $department, array $attributes): Department
    {
        $department->fill($attributes)->save();

        return $department->refresh();
    }

    public function delete(Department $department): void
    {
        $department->delete();
    }
}
