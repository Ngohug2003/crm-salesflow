<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\TaskFilterData;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepository
{
    public function findById(int $id): ?Task;

    public function findVisibleForUserOrFail(User $user, int $id): Task;

    /** @return LengthAwarePaginator<int, Task> */
    public function paginateForUser(User $user, TaskFilterData $filters, int $perPage = 15): LengthAwarePaginator;

    /** @return Collection<int, Task> */
    public function getForSubject(User $user, string $subjectType, int $subjectId): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): Task;

    /** @param array<string, mixed> $data */
    public function update(Task $task, array $data): Task;

    public function delete(Task $task): bool;
}
