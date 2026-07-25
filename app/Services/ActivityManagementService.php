<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ActivityFilterData;
use App\Models\Activity;
use App\Models\User;
use App\Repositories\Contracts\ActivityRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class ActivityManagementService
{
    public function __construct(
        private ActivityRepository $activities,
        private SystemAuditService $audit,
    ) {}

    /** @return Collection<int, Activity> */
    public function getForSubject(User $actor, Model $subject, ?ActivityFilterData $filter = null): Collection
    {
        Gate::forUser($actor)->authorize('viewAny', Activity::class);

        return $this->activities->getForSubject($actor, $subject, $filter);
    }

    /** @param array<string, mixed> $data */
    public function createActivity(User $actor, Model $subject, array $data): Activity
    {
        Gate::forUser($actor)->authorize('create', Activity::class);

        return DB::transaction(function () use ($actor, $subject, $data): Activity {
            $activityData = array_merge($data, [
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'user_id' => $data['user_id'] ?? $actor->getKey(),
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
                'performed_at' => $data['performed_at'] ?? now(),
            ]);

            $activity = $this->activities->create($activityData);

            $type = $activity->activity_type;

            $this->audit->record(
                $actor,
                $activity,
                'created',
                "Tạo mới tương tác '{$activity->title}' ({$type->label()})",
                [],
                [
                    'activity_type' => $type->value,
                    'title' => $activity->title,
                    'subject_type' => $activity->subject_type,
                    'subject_id' => $activity->subject_id,
                ],
            );

            return $activity;
        });
    }

    /** @param array<string, mixed> $data */
    public function updateActivity(User $actor, int $id, array $data): Activity
    {
        return DB::transaction(function () use ($actor, $id, $data): Activity {
            $activity = $this->activities->findVisibleForUpdateOrFail($actor, $id);
            Gate::forUser($actor)->authorize('update', $activity);

            $oldType = $activity->activity_type;

            $oldSnapshot = [
                'title' => $activity->title,
                'activity_type' => $oldType->value,
                'description' => $activity->description,
            ];

            $updatedData = array_merge($data, [
                'updated_by' => $actor->getKey(),
            ]);

            $updatedActivity = $this->activities->update($activity, $updatedData);

            $newType = $updatedActivity->activity_type;

            $newSnapshot = [
                'title' => $updatedActivity->title,
                'activity_type' => $newType->value,
                'description' => $updatedActivity->description,
            ];

            $this->audit->record(
                $actor,
                $updatedActivity,
                'updated',
                "Cập nhật tương tác '{$updatedActivity->title}'",
                $oldSnapshot,
                $newSnapshot,
            );

            return $updatedActivity;
        });
    }

    public function deleteActivity(User $actor, int $id): bool
    {
        return DB::transaction(function () use ($actor, $id): bool {
            $activity = $this->activities->findVisibleForUpdateOrFail($actor, $id);
            Gate::forUser($actor)->authorize('delete', $activity);

            $title = $activity->title;
            $result = $this->activities->delete($activity);

            if ($result) {
                $this->audit->record(
                    $actor,
                    $activity,
                    'deleted',
                    "Xóa tương tác '{$title}'",
                    ['title' => $title],
                    [],
                );
            }

            return $result;
        });
    }
}
