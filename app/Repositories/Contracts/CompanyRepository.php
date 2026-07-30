<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\CompanyFilterData;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CompanyRepository
{
    /**
     * @return LengthAwarePaginator<int, Company>
     */
    public function paginateVisible(User $actor, CompanyFilterData $filters, int $perPage = 15): LengthAwarePaginator;

    public function findVisibleOrFail(User $actor, int $id): Company;

    public function findVisibleForUpdateOrFail(User $actor, int $id): Company;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company;

    public function softDelete(Company $company): Company;
}
