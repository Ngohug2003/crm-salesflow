<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadStatus;
use App\Events\LeadStatusChanged;
use App\Exceptions\LeadWorkflowException;
use App\Models\Lead;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\LeadWorkflowRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class LeadStatusTransitionService
{
    /** @var array<string, list<LeadStatus>> */
    private const array TRANSITIONS = [
        'new' => [LeadStatus::Contacted, LeadStatus::Unqualified, LeadStatus::Lost],
        'contacted' => [LeadStatus::Qualified, LeadStatus::Unqualified, LeadStatus::Lost],
        'qualified' => [LeadStatus::Contacted, LeadStatus::Lost],
        'unqualified' => [LeadStatus::New],
        'lost' => [LeadStatus::New],
        'converted' => [],
    ];

    public function __construct(
        private LeadRepository $leads,
        private LeadWorkflowRepository $workflow,
        private SystemAuditService $audit,
    ) {}

    /** @return list<LeadStatus> */
    public function availableTransitions(LeadStatus $status): array
    {
        return self::TRANSITIONS[$status->value];
    }

    public function transition(User $actor, int $leadId, string $targetStatus, ?string $reason): Lead
    {
        $target = LeadStatus::tryFrom($targetStatus);

        if ($target === null) {
            throw new LeadWorkflowException('targetStatus', 'Trạng thái đích không hợp lệ.');
        }

        return DB::transaction(function () use ($actor, $leadId, $target, $reason): Lead {
            $lead = $this->leads->findVisibleForUpdateOrFail($actor, $leadId);
            Gate::forUser($actor)->authorize('update', $lead);
            $reason = $this->normalizeReason($target, $reason);
            $current = $lead->getAttribute('status');
            $current = $current instanceof LeadStatus ? $current : LeadStatus::from((string) $current);

            if (! in_array($target, $this->availableTransitions($current), true)) {
                throw new LeadWorkflowException(
                    'targetStatus',
                    "Không thể chuyển từ trạng thái {$current->label()} sang {$target->label()}.",
                );
            }

            $savedLead = $this->leads->update($lead, [
                'status' => $target,
                'updated_by' => $actor->getKey(),
            ]);

            $this->workflow->createStatusHistory([
                'lead_id' => $savedLead->getKey(),
                'from_status' => $current,
                'to_status' => $target,
                'changed_by' => $actor->getKey(),
                'reason' => $reason,
            ]);

            $this->audit->record(
                $actor,
                $savedLead,
                'status_changed',
                'Chuyển trạng thái Lead',
                ['status' => $current->value],
                ['status' => $target->value],
                ['reason' => $reason],
            );

            LeadStatusChanged::dispatch(
                $savedLead->getKey(),
                $current->value,
                $target->value,
                $actor->getKey(),
            );

            return $savedLead;
        });
    }

    private function normalizeReason(LeadStatus $target, ?string $reason): ?string
    {
        $reason = $reason === null ? null : trim($reason);
        $reason = $reason === '' ? null : $reason;

        if (in_array($target, [LeadStatus::Unqualified, LeadStatus::Lost], true) && $reason === null) {
            throw new LeadWorkflowException('statusReason', 'Vui lòng nhập lý do khi loại hoặc đánh mất Lead.');
        }

        if ($reason !== null && mb_strlen($reason) > 500) {
            throw new LeadWorkflowException('statusReason', 'Lý do chuyển trạng thái không được vượt quá 500 ký tự.');
        }

        return $reason;
    }
}
