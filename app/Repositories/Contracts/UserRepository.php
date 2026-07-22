<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\UserListFilters;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

interface UserRepository
{
    /** @return Builder<User> */
    public function visibleTo(User $actor): Builder;

    /** @return LengthAwarePaginator<int, User> */
    public function paginateVisibleTo(User $actor, UserListFilters $filters, int $perPage = 15): LengthAwarePaginator;

    public function findVisibleOrFail(User $actor, int $userId): User;

    /** @param array{name: string, email: string, department_id: ?int, is_active: bool, password?: string, email_verified_at?: Carbon} $attributes */
    public function create(array $attributes): User;

    /** @param array{name: string, email: string, department_id: ?int, is_active: bool, password?: string} $attributes */
    public function update(User $user, array $attributes): User;
}
