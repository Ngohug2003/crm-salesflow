<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\ActivityFilterData;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface ActivityRepository
{
    public function findVisibleOrFail(User $actor, int $id): Activity;

    public function findVisibleForUpdateOrFail(User $actor, int $id): Activity;

    /** @return Collection<int, Activity> */
    public function getForSubject(User $actor, Model $subject, ?ActivityFilterData $filter = null): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): Activity;

    /** @param array<string, mixed> $data */
    public function update(Activity $activity, array $data): Activity;

    public function delete(Activity $activity): bool;
}
