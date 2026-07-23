<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadStatus;
use App\Events\LeadAssigned;
use App\Exceptions\DuplicateLeadException;
use App\Exceptions\LeadWorkflowException;
use App\Models\Lead;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\LeadWorkflowRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class LeadLifecycleService
{
    public function __construct(
        private LeadRepository $leads,
        private LeadWorkflowRepository $workflow,
        private UserRepository $users,
        private SystemAuditService $audit,
        private DuplicateLeadService $duplicates,
    ) {}

    public function delete(User $actor, int $leadId, ?string $reason): Lead
    {
        $reason = $this->reason($reason, 'deleteReason');

        return DB::transaction(function () use ($actor, $leadId, $reason): Lead {
            $lead = $this->leads->findVisibleForUpdateOrFail($actor, $leadId);
            Gate::forUser($actor)->authorize('delete', $lead);
            $old = $this->snapshot($lead);
            $deletedLead = $this->leads->softDelete($lead);

            $this->audit->record(
                $actor,
                $deletedLead,
                'deleted',
                'Đưa Lead vào thùng rác',
                $old,
                [...$old, 'deleted_at' => $deletedLead->deleted_at?->toISOString()],
                ['reason' => $reason],
            );

            return $deletedLead;
        });
    }

    public function restore(
        User $actor,
        int $leadId,
        ?string $reason,
        ?string $duplicateConfirmedSignature = null,
        ?string $duplicateOverrideReason = null,
    ): Lead {
        $reason = $this->reason($reason, 'restoreReason');
        $lead = $this->leads->findTrashedVisibleForUpdateOrFail($actor, $leadId);
        Gate::forUser($actor)->authorize('restore', $lead);

        // Preflight duplicate check with active leads
        $contactAttributes = [
            'email' => $lead->email,
            'phone' => $lead->phone,
            'secondary_phone' => $lead->secondary_phone,
        ];
        $currentSignature = $this->duplicates->signature($contactAttributes);
        $candidates = $this->duplicates->candidates($actor, $contactAttributes, $lead->id);
        $activeCandidates = array_values(array_filter($candidates, fn ($c) => ! $c['trashed']));

        $duplicateOverride = false;
        if ($activeCandidates !== []) {
            if ($duplicateConfirmedSignature !== $currentSignature
                || empty(trim((string) $duplicateOverrideReason))
                || mb_strlen(trim((string) $duplicateOverrideReason)) < 10
            ) {
                throw new DuplicateLeadException($activeCandidates, $currentSignature);
            }
            $duplicateOverride = true;
        }

        return DB::transaction(function () use ($actor, $lead, $reason, $duplicateOverride, $activeCandidates, $duplicateOverrideReason): Lead {
            $old = $this->snapshot($lead);

            if ($lead->owner_id !== null && ! $this->users->activeExists($lead->owner_id)) {
                $previousOwnerId = $lead->owner_id;
                $lead = $this->leads->update($lead, [
                    'owner_id' => null,
                    'updated_by' => $actor->getKey(),
                ]);
                $this->workflow->createAssignmentHistory([
                    'lead_id' => $lead->getKey(),
                    'previous_owner_id' => $previousOwnerId,
                    'new_owner_id' => null,
                    'previous_department_id' => $lead->department_id,
                    'new_department_id' => $lead->department_id,
                    'changed_by' => $actor->getKey(),
                    'reason' => 'Tự động bỏ phân công khi khôi phục vì owner không hoạt động',
                ]);
                LeadAssigned::dispatch(
                    $lead->getKey(),
                    $previousOwnerId,
                    null,
                    $actor->getKey(),
                );
            } else {
                $lead = $this->leads->update($lead, ['updated_by' => $actor->getKey()]);
            }

            $restoredLead = $this->leads->restore($lead);
            $new = $this->snapshot($restoredLead);

            $metadata = ['reason' => $reason];
            if ($duplicateOverride) {
                $metadata['duplicate_override'] = [
                    'candidate_ids' => collect($activeCandidates)->pluck('id')->all(),
                    'reason' => trim((string) $duplicateOverrideReason),
                ];
            }

            $this->audit->record(
                $actor,
                $restoredLead,
                'restored',
                'Khôi phục Lead',
                $old,
                $new,
                $metadata,
            );

            return $restoredLead;
        });
    }

    private function reason(?string $reason, string $field): ?string
    {
        $reason = $reason === null ? null : trim($reason);

        if ($reason !== null && mb_strlen($reason) > 500) {
            throw new LeadWorkflowException($field, 'Lý do không được vượt quá 500 ký tự.');
        }

        return $reason === '' ? null : $reason;
    }

    /** @return array<string, mixed> */
    private function snapshot(Lead $lead): array
    {
        $status = $lead->getAttribute('status');

        return [
            'owner_id' => $lead->owner_id,
            'department_id' => $lead->department_id,
            'status' => $status instanceof LeadStatus ? $status->value : (string) $status,
            'deleted_at' => $lead->deleted_at?->toISOString(),
        ];
    }

    /**
     * Merge a source lead into a target lead.
     * Note: This is only a contract placeholder, not implemented in this phase.
     *
     * @throws \LogicException
     */
    public function merge(User $actor, int $sourceLeadId, int $targetLeadId): void
    {
        throw new \LogicException('Tính năng gộp Lead (merge) chưa được triển khai ở giai đoạn này.');
    }
}
