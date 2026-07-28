<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\CompanyFilterData;
use App\Models\Company;
use App\Models\User;
use App\Repositories\Contracts\CompanyRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentCompanyRepository implements CompanyRepository
{
    /** @var array<string, string> */
    private const array SORT_COLUMNS = [
        'created_at' => 'companies.created_at',
        'name' => 'companies.name',
        'annual_revenue' => 'companies.annual_revenue',
        'tax_code' => 'companies.tax_code',
    ];

    public function __construct(private DataScopeService $dataScope) {}

    /** @return Builder<Company> */
    private function visibleTo(User $actor): Builder
    {
        return $this->dataScope->apply(
            Company::query(),
            $actor,
            'owner_id',
            'department_id',
        );
    }

    /** @return Builder<Company> */
    private function filteredVisibleTo(User $actor, CompanyFilterData $filters): Builder
    {
        $search = trim($filters->search);
        $sortColumn = self::SORT_COLUMNS[$filters->sortBy] ?? self::SORT_COLUMNS['created_at'];
        $sortDirection = strtolower($filters->sortDirection) === 'asc' ? 'asc' : 'desc';

        return $this->visibleTo($actor)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = "%{$search}%";

                $query->where(function (Builder $sub) use ($like): void {
                    $sub->whereLike('name', $like, caseSensitive: false)
                        ->orWhereLike('tax_code', $like, caseSensitive: false)
                        ->orWhereLike('email', $like, caseSensitive: false)
                        ->orWhereLike('phone', $like, caseSensitive: false)
                        ->orWhereLike('industry', $like, caseSensitive: false);
                });
            })
            ->when($filters->industry !== null && $filters->industry !== '', fn (Builder $q) => $q->where('industry', $filters->industry))
            ->when($filters->companySize !== null && $filters->companySize !== '', fn (Builder $q) => $q->where('company_size', $filters->companySize))
            ->when($filters->ownerId !== null, fn (Builder $q) => $q->where('owner_id', $filters->ownerId))
            ->when($filters->departmentId !== null, fn (Builder $q) => $q->where('department_id', $filters->departmentId))
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('companies.id', 'desc');
    }

    public function paginateVisible(User $actor, CompanyFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return $this->filteredVisibleTo($actor, $filters)
            ->with(['owner', 'department'])
            ->paginate($perPage);
    }

    public function findVisibleOrFail(User $actor, int $id): Company
    {
        /** @var Company */
        return $this->visibleTo($actor)
            ->with(['owner', 'department', 'provinceUnit', 'ward', 'createdBy', 'updatedBy', 'contacts'])
            ->findOrFail($id);
    }

    public function findVisibleForUpdateOrFail(User $actor, int $id): Company
    {
        /** @var Company */
        return $this->visibleTo($actor)
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function create(array $data): Company
    {
        return Company::query()->create($data);
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->refresh();
    }

    public function softDelete(Company $company): Company
    {
        $company->delete();

        return $company;
    }
}
