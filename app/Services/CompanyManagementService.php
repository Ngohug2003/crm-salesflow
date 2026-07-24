<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CompanyFilterData;
use App\Models\Company;
use App\Models\User;
use App\Repositories\Contracts\CompanyRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class CompanyManagementService
{
    public function __construct(
        private CompanyRepository $companies,
        private SystemAuditService $audit,
    ) {}

    /** @return LengthAwarePaginator<int, Company> */
    public function list(User $actor, CompanyFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Company::class);

        return $this->companies->paginateVisible($actor, $filters, $perPage);
    }

    public function get(User $actor, int $id): Company
    {
        $company = $this->companies->findVisibleOrFail($actor, $id);
        Gate::forUser($actor)->authorize('view', $company);

        return $company;
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): Company
    {
        Gate::forUser($actor)->authorize('create', Company::class);

        return DB::transaction(function () use ($actor, $data): Company {
            $ownerId = isset($data['owner_id']) && $data['owner_id'] !== '' ? (int) $data['owner_id'] : null;

            if ($ownerId === null && ! $actor->hasAnyRole(['super-admin', 'admin'])) {
                $ownerId = $actor->getKey();
            }

            $departmentId = null;
            if ($ownerId !== null) {
                $ownerUser = User::query()->find($ownerId);
                $departmentId = $ownerUser?->department_id;
            }

            $payload = array_merge($data, [
                'owner_id' => $ownerId,
                'department_id' => $departmentId,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]);

            $company = $this->companies->create($payload);

            $this->audit->record(
                $actor,
                $company,
                'created',
                'Tạo mới Doanh nghiệp',
                null,
                $this->snapshot($company),
            );

            return $company;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, int $id, array $data): Company
    {
        return DB::transaction(function () use ($actor, $id, $data): Company {
            $company = $this->companies->findVisibleForUpdateOrFail($actor, $id);
            Gate::forUser($actor)->authorize('update', $company);

            $oldSnapshot = $this->snapshot($company);

            $ownerId = isset($data['owner_id']) && $data['owner_id'] !== '' ? (int) $data['owner_id'] : $company->owner_id;
            $departmentId = $company->department_id;

            if ($ownerId !== $company->owner_id && $ownerId !== null) {
                $ownerUser = User::query()->find($ownerId);
                $departmentId = $ownerUser?->department_id;
            }

            $payload = array_merge($data, [
                'owner_id' => $ownerId,
                'department_id' => $departmentId,
                'updated_by' => $actor->getKey(),
            ]);

            $updatedCompany = $this->companies->update($company, $payload);

            $this->audit->record(
                $actor,
                $updatedCompany,
                'updated',
                'Cập nhật thông tin Doanh nghiệp',
                $oldSnapshot,
                $this->snapshot($updatedCompany),
            );

            return $updatedCompany;
        });
    }

    public function delete(User $actor, int $id): Company
    {
        return DB::transaction(function () use ($actor, $id): Company {
            $company = $this->companies->findVisibleForUpdateOrFail($actor, $id);
            Gate::forUser($actor)->authorize('delete', $company);

            $oldSnapshot = $this->snapshot($company);
            $deletedCompany = $this->companies->softDelete($company);

            $this->audit->record(
                $actor,
                $deletedCompany,
                'deleted',
                'Xóa Doanh nghiệp',
                $oldSnapshot,
                [...$oldSnapshot, 'deleted_at' => $deletedCompany->deleted_at?->toISOString()],
            );

            return $deletedCompany;
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(Company $company): array
    {
        return [
            'id' => $company->getKey(),
            'name' => $company->name,
            'tax_code' => $company->tax_code,
            'website' => $company->website,
            'email' => $company->email,
            'phone' => $company->phone,
            'industry' => $company->industry,
            'company_size' => $company->company_size,
            'annual_revenue' => $company->annual_revenue,
            'owner_id' => $company->owner_id,
            'department_id' => $company->department_id,
        ];
    }
}
