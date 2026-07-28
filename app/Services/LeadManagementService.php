<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DataScope;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Events\LeadAssigned;
use App\Events\LeadStatusChanged;
use App\Exceptions\DuplicateLeadException;
use App\Models\Lead;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\LeadWorkflowRepository;
use App\Repositories\Contracts\UserRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class LeadManagementService
{
    public function __construct(
        private LeadRepository $leads,
        private LeadWorkflowRepository $workflow,
        private UserRepository $users,
        private DataScopeService $dataScope,
        private SystemAuditService $audit,
        private DuplicateLeadService $duplicates,
        private AdministrativeUnitService $administrativeUnits,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function save(
        User $actor,
        ?Lead $lead,
        array $attributes,
        ?string $duplicateConfirmedSignature = null,
        ?string $duplicateOverrideReason = null,
    ): Lead {
        Gate::forUser($actor)->authorize($lead === null ? 'create' : 'update', $lead ?? Lead::class);
        $attributes = $this->administrativeUnits->normalizeAddressPayload($attributes);

        // Duplicate detection check
        $currentSignature = $this->duplicates->signature($attributes);
        $candidates = $this->duplicates->candidates($actor, $attributes, $lead?->id);

        $duplicateOverride = false;
        if ($candidates !== []) {
            if ($duplicateConfirmedSignature !== $currentSignature
                || empty(trim((string) $duplicateOverrideReason))
                || mb_strlen(trim((string) $duplicateOverrideReason)) < 10
            ) {
                throw new DuplicateLeadException($candidates, $currentSignature);
            }
            $duplicateOverride = true;
        }

        /** @var list<int> $tagIds */
        $tagIds = $attributes['tag_ids'];
        unset($attributes['tag_ids']);

        if ($lead === null) {
            $owner = $this->resolveOwner($actor, $attributes['owner_id']);
            $this->authorizeInitialAssignment($actor, $owner);
            $attributes['owner_id'] = $owner?->getKey();
            $attributes['department_id'] = $this->departmentId($actor, $owner);
        } else {
            if ($attributes['owner_id'] !== $lead->owner_id) {
                throw new AuthorizationException('Hãy dùng chức năng phân công để thay đổi người phụ trách.');
            }

            $attributes['owner_id'] = $lead->owner_id;
            $attributes['department_id'] = $lead->department_id;
        }

        return DB::transaction(function () use ($actor, $lead, $attributes, $tagIds, $duplicateOverride, $candidates, $duplicateOverrideReason): Lead {
            $oldValues = $lead === null ? null : $this->auditSnapshot($lead);

            if ($lead === null) {
                $savedLead = $this->leads->create([
                    ...$attributes,
                    'status' => LeadStatus::New,
                    'created_by' => $actor->getKey(),
                    'updated_by' => $actor->getKey(),
                ]);
            } else {
                $savedLead = $this->leads->update($lead, [
                    ...$attributes,
                    'updated_by' => $actor->getKey(),
                ]);
            }

            $savedLead = $this->leads->syncTags($savedLead, $tagIds);

            if ($lead === null) {
                $this->recordInitialWorkflow($actor, $savedLead);
            }

            $newValues = $this->auditSnapshot($savedLead);

            $metadata = [];
            if ($duplicateOverride) {
                $metadata['duplicate_override'] = [
                    'candidate_ids' => collect($candidates)->pluck('id')->all(),
                    'reason' => trim((string) $duplicateOverrideReason),
                ];
            }

            if ($oldValues !== $newValues || $duplicateOverride) {
                $this->audit->record(
                    $actor,
                    $savedLead,
                    $lead === null ? 'created' : 'updated',
                    $lead === null ? 'Tạo Lead' : 'Cập nhật Lead',
                    $oldValues,
                    $newValues,
                    $metadata,
                );
            }

            return $savedLead;
        });
    }

    private function resolveOwner(User $actor, mixed $requestedOwnerId): ?User
    {
        $scope = $this->dataScope->resolve($actor);
        $ownerId = is_int($requestedOwnerId) ? $requestedOwnerId : null;

        if ($scope === DataScope::Owned) {
            if ($ownerId !== null && $ownerId !== $actor->getKey()) {
                throw new AuthorizationException('Bạn chỉ có thể tự nhận Lead cho chính mình.');
            }

            return $actor;
        }

        if ($ownerId === null) {
            return null;
        }

        $owner = $this->users->findVisibleActiveUser($actor, $ownerId);

        if (! $owner instanceof User) {
            throw new AuthorizationException('Người phụ trách nằm ngoài phạm vi bạn được quản lý.');
        }

        return $owner;
    }

    private function authorizeInitialAssignment(User $actor, ?User $owner): void
    {
        $newOwnerId = $owner?->getKey();

        if ($newOwnerId === null || $newOwnerId === $actor->getKey()) {
            return;
        }

        if (! $actor->can('leads.assign')) {
            throw new AuthorizationException('Bạn không có quyền thay đổi người phụ trách Lead.');
        }
    }

    private function departmentId(User $actor, ?User $owner): ?int
    {
        if ($owner !== null) {
            return $owner->department_id;
        }

        return $this->dataScope->resolve($actor) === DataScope::Department
            ? $actor->department_id
            : null;
    }

    private function recordInitialWorkflow(User $actor, Lead $lead): void
    {
        $this->workflow->createStatusHistory([
            'lead_id' => $lead->getKey(),
            'from_status' => null,
            'to_status' => LeadStatus::New,
            'changed_by' => $actor->getKey(),
            'reason' => 'Khởi tạo Lead',
        ]);

        LeadStatusChanged::dispatch(
            $lead->getKey(),
            null,
            LeadStatus::New->value,
            $actor->getKey(),
        );

        if ($lead->owner_id === null && $lead->department_id === null) {
            return;
        }

        $this->workflow->createAssignmentHistory([
            'lead_id' => $lead->getKey(),
            'previous_owner_id' => null,
            'new_owner_id' => $lead->owner_id,
            'previous_department_id' => null,
            'new_department_id' => $lead->department_id,
            'changed_by' => $actor->getKey(),
            'reason' => 'Phân công khi tạo Lead',
        ]);

        LeadAssigned::dispatch(
            $lead->getKey(),
            null,
            $lead->owner_id,
            $actor->getKey(),
        );
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(Lead $lead): array
    {
        $lead->loadMissing('tags:id');

        $status = $lead->getAttribute('status');
        $priority = $lead->getAttribute('priority');

        return [
            'lead_source_id' => $lead->lead_source_id,
            'owner_id' => $lead->owner_id,
            'department_id' => $lead->department_id,
            'full_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'secondary_phone' => $lead->secondary_phone,
            'company_name' => $lead->company_name,
            'job_title' => $lead->job_title,
            'website' => $lead->website,
            'address' => $lead->address,
            'province_id' => $lead->province_id,
            'ward_id' => $lead->ward_id,
            'city' => $lead->city,
            'province' => $lead->province,
            'country' => $lead->country,
            'status' => $status instanceof LeadStatus ? $status->value : (string) $status,
            'priority' => $priority instanceof LeadPriority ? $priority->value : (string) $priority,
            'estimated_value' => $lead->estimated_value,
            'notes' => $lead->notes,
            'tag_ids' => $lead->tags->modelKeys(),
        ];
    }
}
