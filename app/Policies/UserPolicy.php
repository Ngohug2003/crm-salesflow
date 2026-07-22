<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class UserPolicy
{
    public function __construct(private DataScopeService $dataScope) {}

    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->can('users.view') && $this->withinScope($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $this->dataScope->canWrite($actor) && $actor->can('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        return $this->dataScope->canWrite($actor)
            && $actor->can('users.update')
            && $this->withinScope($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->dataScope->canWrite($actor)
            && $actor->can('users.delete')
            && $this->withinScope($actor, $target);
    }

    private function withinScope(User $actor, User $target): bool
    {
        return $this->dataScope->allows(
            $actor,
            (int) $target->getKey(),
            $target->department_id,
        );
    }
}
