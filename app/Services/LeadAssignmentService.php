<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DataScope;
use App\Events\LeadAssigned;
use App\Exceptions\LeadWorkflowException;
use App\Models\Lead;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\LeadWorkflowRepository;
use App\Repositories\Contracts\UserRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class LeadAssignmentService
{
    public function __construct(
        private LeadRepository $leads,
        private LeadWorkflowRepository $workflow,
        private UserRepository $users,
        private DataScopeService $dataScope,
        private SystemAuditService $audit,
    ) {}

    public function assign(User $actor, int $leadId, ?int $ownerId, ?string $reason): Lead
    {
        return DB::transaction(function () use ($actor, $leadId, $ownerId, $reason): Lead {
            $lead = $this->leads->findVisibleForUpdateOrFail($actor, $leadId);
            Gate::forUser($actor)->authorize('assign', $lead);
            $reason = $this->normalizeReason($reason);
            $owner = $this->resolveOwner($actor, $ownerId);
            $departmentId = $owner === null ? $this->unassignedDepartmentId($actor) : $owner->department_id;

            if ($lead->owner_id === $owner?->getKey() && $lead->department_id === $departmentId) {
                throw new LeadWorkflowException('ownerId', 'Hãy chọn người phụ trách khác trước khi lưu phân công.');
            }

            $oldOwnerId = $lead->owner_id;
            $oldDepartmentId = $lead->department_id;
            $savedLead = $this->leads->update($lead, [
                'owner_id' => $owner?->getKey(),
                'department_id' => $departmentId,
                'updated_by' => $actor->getKey(),
            ]);

            $this->workflow->createAssignmentHistory([
                'lead_id' => $savedLead->getKey(),
                'previous_owner_id' => $oldOwnerId,
                'new_owner_id' => $savedLead->owner_id,
                'previous_department_id' => $oldDepartmentId,
                'new_department_id' => $savedLead->department_id,
                'changed_by' => $actor->getKey(),
                'reason' => $reason,
            ]);

            $this->audit->record(
                $actor,
                $savedLead,
                'assigned',
                'Phân công Lead',
                ['owner_id' => $oldOwnerId, 'department_id' => $oldDepartmentId],
                ['owner_id' => $savedLead->owner_id, 'department_id' => $savedLead->department_id],
                ['reason' => $reason],
            );

            LeadAssigned::dispatch(
                $savedLead->getKey(),
                $oldOwnerId,
                $savedLead->owner_id,
                $actor->getKey(),
            );

            return $savedLead->load(['owner:id,name,email', 'department:id,name,code']);
        });
    }

    private function resolveOwner(User $actor, ?int $ownerId): ?User
    {
        if ($ownerId === null) {
            return null;
        }

        $owner = $this->users->visibleTo($actor)
            ->where('is_active', true)
            ->find($ownerId);

        if (! $owner instanceof User) {
            throw new AuthorizationException('Người phụ trách nằm ngoài phạm vi bạn được quản lý.');
        }

        return $owner;
    }

    private function unassignedDepartmentId(User $actor): ?int
    {
        return $this->dataScope->resolve($actor) === DataScope::Department
            ? $actor->department_id
            : null;
    }

    private function normalizeReason(?string $reason): ?string
    {
        $reason = $reason === null ? null : trim($reason);

        if ($reason !== null && mb_strlen($reason) > 500) {
            throw new LeadWorkflowException('assignmentReason', 'Lý do phân công không được vượt quá 500 ký tự.');
        }

        return $reason === '' ? null : $reason;
    }
}
