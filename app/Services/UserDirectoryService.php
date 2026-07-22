<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\UserListFilters;
use App\Enums\DataScope;
use App\Models\Department;
use App\Models\User;
use App\Repositories\Contracts\DepartmentRepository;
use App\Repositories\Contracts\UserRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final readonly class UserDirectoryService
{
    public function __construct(
        private UserRepository $users,
        private DepartmentRepository $departments,
        private DataScopeService $dataScope,
    ) {}

    /** @return LengthAwarePaginator<int, User> */
    public function paginate(User $actor, UserListFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginateVisibleTo($actor, $filters, $perPage);
    }

    /** @return Collection<int, Department> */
    public function departmentOptions(User $actor): Collection
    {
        $onlyIds = match ($this->dataScope->resolve($actor)) {
            DataScope::Department, DataScope::Owned => $actor->department_id === null
                ? []
                : [$actor->department_id],
            DataScope::All, DataScope::ReadOnly => null,
        };

        return $this->departments->userFilterOptions($onlyIds);
    }

    /** @return Collection<int, Department> */
    public function formDepartmentOptions(User $actor, ?int $selectedId = null): Collection
    {
        $departments = $this->departments->userFormOptions($selectedId);

        return match ($this->dataScope->resolve($actor)) {
            DataScope::Department, DataScope::Owned => $departments
                ->filter(fn (Department $department): bool => $department->getKey() === $actor->department_id)
                ->values(),
            DataScope::All, DataScope::ReadOnly => $departments,
        };
    }

    /** @return array<string, string> */
    public function roleOptions(): array
    {
        /** @var array<string, array{label: string}> $roles */
        $roles = config('crm.rbac.roles', []);

        return array_map(
            static fn (array $role): string => $role['label'],
            $roles,
        );
    }
}
