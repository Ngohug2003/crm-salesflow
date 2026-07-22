<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\UserListFilters;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentUserRepository implements UserRepository
{
    public function __construct(private DataScopeService $dataScope) {}

    public function visibleTo(User $actor): Builder
    {
        return $this->dataScope->apply(User::query(), $actor, 'id', 'department_id');
    }

    public function paginateVisibleTo(User $actor, UserListFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        $search = trim($filters->search);

        return $this->visibleTo($actor)
            ->with([
                'department:id,name,code',
                'roles:id,name',
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = "%{$search}%";

                $query->where(function (Builder $query) use ($like): void {
                    $query->whereLike('name', $like, caseSensitive: false)
                        ->orWhereLike('email', $like, caseSensitive: false);
                });
            })
            ->when(
                ctype_digit($filters->department) && (int) $filters->department > 0,
                fn (Builder $query): Builder => $query->where('department_id', (int) $filters->department),
            )
            ->when(
                $filters->department === 'unassigned',
                fn (Builder $query): Builder => $query->whereNull('department_id'),
            )
            ->when(
                $filters->role !== 'all',
                fn (Builder $query): Builder => $query->whereHas(
                    'roles',
                    fn (Builder $roleQuery): Builder => $roleQuery->where('name', $filters->role),
                ),
            )
            ->when(
                $filters->status === 'active',
                fn (Builder $query): Builder => $query->where('is_active', true),
            )
            ->when(
                $filters->status === 'inactive',
                fn (Builder $query): Builder => $query->where('is_active', false),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function findVisibleOrFail(User $actor, int $userId): User
    {
        return $this->visibleTo($actor)->findOrFail($userId);
    }

    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes)->save();

        return $user->refresh();
    }

    public function syncRoles(User $user, array $roles): User
    {
        $user->syncRoles($roles);

        return $user->load('roles:id,name');
    }

    public function lockActiveAdministratorIds(array $administratorRoles): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas(
                'roles',
                fn (Builder $query): Builder => $query->whereIn('name', $administratorRoles),
            )
            ->lockForUpdate()
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }
}
