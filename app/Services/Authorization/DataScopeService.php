<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Enums\DataScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final readonly class DataScopeService
{
    public function __construct(private DataScopeResolver $resolver) {}

    public function resolve(User $user): DataScope
    {
        return $this->resolver->resolve($user);
    }

    public function canWrite(User $user): bool
    {
        return ! $this->resolve($user)->isReadOnly();
    }

    public function allows(User $actor, int $ownerId, ?int $departmentId): bool
    {
        return match ($this->resolve($actor)) {
            DataScope::All, DataScope::ReadOnly => true,
            DataScope::Department => $actor->department_id !== null
                && $actor->department_id === $departmentId,
            DataScope::Owned => $actor->getKey() === $ownerId,
        };
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function apply(
        Builder $query,
        User $actor,
        string $ownerColumn = 'owner_id',
        string $departmentColumn = 'department_id',
    ): Builder {
        $model = $query->getModel();

        return match ($this->resolve($actor)) {
            DataScope::All, DataScope::ReadOnly => $query,
            DataScope::Department => $actor->department_id === null
                ? $query->whereRaw('1 = 0')
                : $query->where($model->qualifyColumn($departmentColumn), $actor->department_id),
            DataScope::Owned => $query->where($model->qualifyColumn($ownerColumn), $actor->getKey()),
        };
    }
}
