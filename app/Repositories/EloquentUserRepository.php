<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentUserRepository implements UserRepository
{
    public function __construct(private DataScopeService $dataScope) {}

    public function visibleTo(User $actor): Builder
    {
        return $this->dataScope->apply(User::query(), $actor, 'id', 'department_id');
    }

    public function findVisibleOrFail(User $actor, int $userId): User
    {
        return $this->visibleTo($actor)->findOrFail($userId);
    }
}
