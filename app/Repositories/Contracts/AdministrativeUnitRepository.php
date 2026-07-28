<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Province;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Collection;

interface AdministrativeUnitRepository
{
    /** @return Collection<int, Province> */
    public function provinceOptions(): Collection;

    /** @return Collection<int, Ward> */
    public function wardOptions(int $provinceId): Collection;

    /** @return Collection<int, Province> */
    public function allProvinces(): Collection;

    /** @return Collection<int, Ward> */
    public function allWards(): Collection;

    public function findActiveProvince(int $provinceId): ?Province;

    public function findActiveWardInProvince(int $wardId, int $provinceId): ?Ward;

    /** @param list<array<string, mixed>> $rows */
    public function upsertProvinces(array $rows): void;

    /** @return array<string, int> */
    public function provinceIdsByCode(): array;

    /** @param list<array<string, mixed>> $rows */
    public function upsertWards(array $rows): void;
}
