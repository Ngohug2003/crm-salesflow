<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface UserRepository
{
    /** @return Builder<User> */
    public function visibleTo(User $actor): Builder;

    public function findVisibleOrFail(User $actor, int $userId): User;
}
