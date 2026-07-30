<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Province;
use App\Models\Ward;
use App\Repositories\Contracts\AdministrativeUnitRepository;
use Illuminate\Database\Eloquent\Collection;

final class EloquentAdministrativeUnitRepository implements AdministrativeUnitRepository
{
    public function provinceOptions(): Collection
    {
        return Province::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'full_name']);
    }

    public function wardOptions(int $provinceId): Collection
    {
        return Ward::query()
            ->active()
            ->where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'province_id', 'code', 'name', 'full_name']);
    }

    public function allProvinces(): Collection
    {
        return Province::query()->get();
    }

    public function allWards(): Collection
    {
        return Ward::query()->get();
    }

    public function findActiveProvince(int $provinceId): ?Province
    {
        return Province::query()->active()->find($provinceId);
    }

    public function findActiveWardInProvince(int $wardId, int $provinceId): ?Ward
    {
        return Ward::query()
            ->active()
            ->where('province_id', $provinceId)
            ->find($wardId);
    }

    public function upsertProvinces(array $rows): void
    {
        Province::query()->upsert(
            $rows,
            ['code'],
            [
                'name',
                'name_en',
                'full_name',
                'full_name_en',
                'code_name',
                'administrative_unit_id',
                'administrative_unit_short_name',
                'administrative_unit_full_name',
                'administrative_unit_short_name_en',
                'administrative_unit_full_name_en',
                'is_active',
                'updated_at',
            ],
        );
    }

    public function provinceIdsByCode(): array
    {
        /** @var array<string, int> */
        return Province::query()
            ->pluck('id', 'code')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function upsertWards(array $rows): void
    {
        Ward::query()->upsert(
            $rows,
            ['code'],
            [
                'province_id',
                'name',
                'name_en',
                'full_name',
                'full_name_en',
                'code_name',
                'administrative_unit_id',
                'administrative_unit_short_name',
                'administrative_unit_full_name',
                'administrative_unit_short_name_en',
                'administrative_unit_full_name_en',
                'is_active',
                'updated_at',
            ],
        );
    }
}
