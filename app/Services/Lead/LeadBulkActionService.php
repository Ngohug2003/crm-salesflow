<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadAssignmentService;
use App\Services\LeadStatusTransitionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class LeadBulkActionService
{
    public function __construct(
        private readonly LeadAssignmentService $assignmentService,
        private readonly LeadStatusTransitionService $statusService,
    ) {}

    /**
     * Bulk assign leads to a new owner with backend re-authorization.
     *
     * @param  list<int>  $leadIds
     * @return array{success: int, failed: int}
     */
    public function bulkAssign(User $actor, array $leadIds, ?int $ownerId, ?string $reason): array
    {
        $success = 0;
        $failed = 0;

        /** @var Collection<int, Lead> $leads */
        $leads = Lead::query()->whereIn('id', $leadIds)->get();

        foreach ($leads as $lead) {
            if (! Gate::forUser($actor)->allows('assign', $lead)) {
                $failed++;

                continue;
            }

            try {
                $this->assignmentService->assign($actor, $lead->getKey(), $ownerId, $reason);
                $success++;
            } catch (Throwable) {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Bulk update status for leads with backend re-authorization and transition validation.
     *
     * @param  list<int>  $leadIds
     * @return array{success: int, failed: int}
     */
    public function bulkUpdateStatus(User $actor, array $leadIds, string $targetStatus, ?string $reason): array
    {
        $success = 0;
        $failed = 0;

        /** @var Collection<int, Lead> $leads */
        $leads = Lead::query()->whereIn('id', $leadIds)->get();

        foreach ($leads as $lead) {
            if (! Gate::forUser($actor)->allows('update', $lead)) {
                $failed++;

                continue;
            }

            try {
                $this->statusService->transition($actor, $lead->getKey(), $targetStatus, $reason);
                $success++;
            } catch (Throwable) {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Bulk attach tag to leads with backend re-authorization.
     *
     * @param  list<int>  $leadIds
     * @return array{success: int, failed: int}
     */
    public function bulkAddTag(User $actor, array $leadIds, int $tagId): array
    {
        $success = 0;
        $failed = 0;

        /** @var Collection<int, Lead> $leads */
        $leads = Lead::query()->whereIn('id', $leadIds)->get();

        foreach ($leads as $lead) {
            if (! Gate::forUser($actor)->allows('update', $lead)) {
                $failed++;

                continue;
            }

            try {
                $lead->tags()->syncWithoutDetaching([$tagId]);
                $success++;
            } catch (Throwable) {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Bulk soft-delete leads to trash with backend re-authorization.
     *
     * @param  list<int>  $leadIds
     * @return array{success: int, failed: int}
     */
    public function bulkDelete(User $actor, array $leadIds): array
    {
        $success = 0;
        $failed = 0;

        /** @var Collection<int, Lead> $leads */
        $leads = Lead::query()->whereIn('id', $leadIds)->get();

        foreach ($leads as $lead) {
            if (! Gate::forUser($actor)->allows('delete', $lead)) {
                $failed++;

                continue;
            }

            try {
                $lead->delete();

                activity()
                    ->causedBy($actor)
                    ->performedOn($lead)
                    ->event('deleted')
                    ->log("Xóa Lead {$lead->full_name} vào thùng rác");

                $success++;
            } catch (Throwable) {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }
}
