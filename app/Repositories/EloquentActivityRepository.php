<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\ActivityFilterData;
use App\Models\Activity;
use App\Models\User;
use App\Repositories\Contracts\ActivityRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final readonly class EloquentActivityRepository implements ActivityRepository
{
    public function findVisibleOrFail(User $actor, int $id): Activity
    {
        /** @var Activity $activity */
        $activity = Activity::query()->with(['user', 'creator'])->findOrFail($id);

        return $activity;
    }

    public function findVisibleForUpdateOrFail(User $actor, int $id): Activity
    {
        /** @var Activity $activity */
        $activity = Activity::query()->lockForUpdate()->findOrFail($id);

        return $activity;
    }

    /** @return Collection<int, Activity> */
    public function getForSubject(User $actor, Model $subject, ?ActivityFilterData $filter = null): Collection
    {
        $query = Activity::query()
            ->with(['user', 'creator'])
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());

        if ($filter !== null) {
            if ($filter->activityType !== null) {
                $query->where('activity_type', $filter->activityType->value);
            }

            if ($filter->search !== null && trim($filter->search) !== '') {
                $search = trim($filter->search);
                $query->where(function ($q) use ($search): void {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            }

            if ($filter->dateFrom !== null && trim($filter->dateFrom) !== '') {
                $query->whereDate('performed_at', '>=', trim($filter->dateFrom));
            }

            if ($filter->dateTo !== null && trim($filter->dateTo) !== '') {
                $query->whereDate('performed_at', '<=', trim($filter->dateTo));
            }
        }

        /** @var Collection<int, Activity> $results */
        $results = $query->orderBy('performed_at', 'desc')->get();

        return $results;
    }

    public function create(array $data): Activity
    {
        /** @var Activity $activity */
        $activity = Activity::query()->create($data);

        return $activity;
    }

    public function update(Activity $activity, array $data): Activity
    {
        $activity->update($data);

        return $activity->refresh();
    }

    public function delete(Activity $activity): bool
    {
        return (bool) $activity->delete();
    }
}
