<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\ActivityFilterData;
use App\Enums\DataScope;
use App\Models\Activity;
use App\Models\User;
use App\Repositories\Contracts\ActivityRepository;
use App\Services\Authorization\DataScopeService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final readonly class EloquentActivityRepository implements ActivityRepository
{
    public function __construct(
        private DataScopeService $dataScope,
    ) {}

    public function findVisibleOrFail(User $actor, int $id): Activity
    {
        $query = Activity::query()->with(['user', 'creator']);
        $this->applyDataScope($query, $actor);

        /** @var Activity $activity */
        $activity = $query->findOrFail($id);

        return $activity;
    }

    public function findVisibleForUpdateOrFail(User $actor, int $id): Activity
    {
        $query = Activity::query()->lockForUpdate();
        $this->applyDataScope($query, $actor);

        /** @var Activity $activity */
        $activity = $query->findOrFail($id);

        return $activity;
    }

    /** @return Collection<int, Activity> */
    public function getForSubject(User $actor, Model $subject, ?ActivityFilterData $filter = null): Collection
    {
        $query = Activity::query()
            ->with(['user', 'creator'])
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
        $this->applyDataScope($query, $actor);

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

    public function getVisibleBetween(User $actor, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $query = Activity::query()
            ->with(['user', 'creator'])
            ->whereBetween('performed_at', [$from, $to]);
        $this->applyDataScope($query, $actor);

        /** @var Collection<int, Activity> */
        return $query->orderBy('performed_at')->get();
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

    /** @param Builder<Activity> $query */
    private function applyDataScope(Builder $query, User $actor): void
    {
        $scope = $this->dataScope->resolve($actor);

        if ($scope === DataScope::All || $scope === DataScope::ReadOnly) {
            return;
        }

        if ($scope === DataScope::Department) {
            if ($actor->department_id === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $departmentId = $actor->department_id;
            $query->where(function (Builder $query) use ($departmentId): void {
                $query
                    ->whereHas('user', fn (Builder $users): Builder => $users->where('department_id', $departmentId))
                    ->orWhereHas('creator', fn (Builder $users): Builder => $users->where('department_id', $departmentId));
            });

            return;
        }

        $userId = $actor->getKey();
        $query->where(function (Builder $query) use ($userId): void {
            $query->where('user_id', $userId)
                ->orWhere('created_by', $userId);
        });
    }
}
