<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ContactFilterData;
use App\Exceptions\DuplicateContactException;
use App\Models\Contact;
use App\Models\User;
use App\Repositories\Contracts\ContactRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class ContactManagementService
{
    public function __construct(
        private ContactRepository $contacts,
        private SystemAuditService $audit,
        private DuplicateContactService $duplicates,
    ) {}

    /** @return LengthAwarePaginator<int, Contact> */
    public function list(User $actor, ContactFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Contact::class);

        return $this->contacts->paginateVisible($actor, $filters, $perPage);
    }

    public function get(User $actor, int $id): Contact
    {
        $contact = $this->contacts->findVisibleOrFail($actor, $id);
        Gate::forUser($actor)->authorize('view', $contact);

        return $contact;
    }

    /** @param array<string, mixed> $data */
    public function create(
        User $actor,
        array $data,
        ?string $duplicateConfirmedSignature = null,
        ?string $duplicateOverrideReason = null,
    ): Contact {
        Gate::forUser($actor)->authorize('create', Contact::class);

        $currentSignature = $this->duplicates->signature($data);
        $candidates = $this->duplicates->candidates($actor, $data);

        $duplicateOverride = false;
        if ($candidates !== []) {
            if ($duplicateConfirmedSignature !== $currentSignature
                || empty(trim((string) $duplicateOverrideReason))
                || mb_strlen(trim((string) $duplicateOverrideReason)) < 10) {
                throw new DuplicateContactException($candidates, $currentSignature);
            }
            $duplicateOverride = true;
        }

        return DB::transaction(function () use ($actor, $data, $duplicateOverride, $duplicateOverrideReason): Contact {
            $ownerId = isset($data['owner_id']) && $data['owner_id'] !== '' ? (int) $data['owner_id'] : null;

            if ($ownerId === null && ! $actor->hasAnyRole(['super-admin', 'admin'])) {
                $ownerId = $actor->getKey();
            }

            $departmentId = null;
            if ($ownerId !== null) {
                $ownerUser = User::query()->find($ownerId);
                $departmentId = $ownerUser?->department_id;
            }

            $firstName = (string) ($data['first_name'] ?? '');
            $lastName = (string) ($data['last_name'] ?? '');
            $fullName = trim("{$lastName} {$firstName}");

            $isPrimary = (bool) ($data['is_primary'] ?? false);
            $companyId = isset($data['company_id']) && $data['company_id'] !== '' ? (int) $data['company_id'] : null;

            if ($isPrimary && $companyId !== null) {
                $this->contacts->resetPrimaryContactsExcept($companyId);
            }

            $payload = array_merge($data, [
                'full_name' => $fullName,
                'company_id' => $companyId,
                'is_primary' => $isPrimary,
                'owner_id' => $ownerId,
                'department_id' => $departmentId,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]);

            $contact = $this->contacts->create($payload);
            $newSnapshot = $this->snapshot($contact);

            if ($duplicateOverride) {
                $newSnapshot['duplicate_override'] = [
                    'reason' => trim((string) $duplicateOverrideReason),
                ];
            }

            $this->audit->record(
                $actor,
                $contact,
                'created',
                'Tạo mới Người liên hệ',
                null,
                $newSnapshot,
            );

            return $contact;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(
        User $actor,
        int $id,
        array $data,
        ?string $duplicateConfirmedSignature = null,
        ?string $duplicateOverrideReason = null,
    ): Contact {
        $currentSignature = $this->duplicates->signature($data);
        $candidates = $this->duplicates->candidates($actor, $data, $id);

        $duplicateOverride = false;
        if ($candidates !== []) {
            if ($duplicateConfirmedSignature !== $currentSignature
                || empty(trim((string) $duplicateOverrideReason))
                || mb_strlen(trim((string) $duplicateOverrideReason)) < 10) {
                throw new DuplicateContactException($candidates, $currentSignature);
            }
            $duplicateOverride = true;
        }

        return DB::transaction(function () use ($actor, $id, $data, $duplicateOverride, $duplicateOverrideReason): Contact {
            $contact = $this->contacts->findVisibleForUpdateOrFail($actor, $id);
            Gate::forUser($actor)->authorize('update', $contact);

            $oldSnapshot = $this->snapshot($contact);

            $ownerId = isset($data['owner_id']) && $data['owner_id'] !== '' ? (int) $data['owner_id'] : $contact->owner_id;
            $departmentId = $contact->department_id;

            if ($ownerId !== $contact->owner_id && $ownerId !== null) {
                $ownerUser = User::query()->find($ownerId);
                $departmentId = $ownerUser?->department_id;
            }

            $firstName = (string) ($data['first_name'] ?? $contact->first_name);
            $lastName = (string) ($data['last_name'] ?? $contact->last_name);
            $fullName = trim("{$lastName} {$firstName}");

            $isPrimary = array_key_exists('is_primary', $data) ? (bool) $data['is_primary'] : $contact->is_primary;
            $companyId = array_key_exists('company_id', $data)
                ? ($data['company_id'] !== '' && $data['company_id'] !== null ? (int) $data['company_id'] : null)
                : $contact->company_id;

            if ($isPrimary && $companyId !== null) {
                $this->contacts->resetPrimaryContactsExcept($companyId, $contact->id);
            }

            $payload = array_merge($data, [
                'full_name' => $fullName,
                'company_id' => $companyId,
                'is_primary' => $isPrimary,
                'owner_id' => $ownerId,
                'department_id' => $departmentId,
                'updated_by' => $actor->getKey(),
            ]);

            $updatedContact = $this->contacts->update($contact, $payload);
            $newSnapshot = $this->snapshot($updatedContact);

            if ($duplicateOverride) {
                $newSnapshot['duplicate_override'] = [
                    'reason' => trim((string) $duplicateOverrideReason),
                ];
            }

            $this->audit->record(
                $actor,
                $updatedContact,
                'updated',
                'Cập nhật thông tin Người liên hệ',
                $oldSnapshot,
                $newSnapshot,
            );

            return $updatedContact;
        });
    }

    public function delete(User $actor, int $id): Contact
    {
        return DB::transaction(function () use ($actor, $id): Contact {
            $contact = $this->contacts->findVisibleForUpdateOrFail($actor, $id);
            Gate::forUser($actor)->authorize('delete', $contact);

            $oldSnapshot = $this->snapshot($contact);
            $deletedContact = $this->contacts->softDelete($contact);

            $this->audit->record(
                $actor,
                $deletedContact,
                'deleted',
                'Xóa Người liên hệ',
                $oldSnapshot,
                [...$oldSnapshot, 'deleted_at' => $deletedContact->deleted_at?->toISOString()],
            );

            return $deletedContact;
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(Contact $contact): array
    {
        return [
            'id' => $contact->getKey(),
            'company_id' => $contact->company_id,
            'full_name' => $contact->full_name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'job_title' => $contact->job_title,
            'is_primary' => $contact->is_primary,
            'owner_id' => $contact->owner_id,
            'department_id' => $contact->department_id,
        ];
    }
}
