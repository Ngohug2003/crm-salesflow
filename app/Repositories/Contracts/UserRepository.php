<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\UserListFilters;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface UserRepository
{
    /** @return Builder<User> */
    public function visibleTo(User $actor): Builder;

    /** @return LengthAwarePaginator<int, User> */
    public function paginateVisibleTo(User $actor, UserListFilters $filters, int $perPage = 15): LengthAwarePaginator;

    public function findVisibleOrFail(User $actor, int $userId): User;
}
