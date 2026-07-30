<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Province;
use App\Models\Ward;
use App\Repositories\Contracts\AdministrativeUnitRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;

final readonly class AdministrativeUnitService
{
    public function __construct(private AdministrativeUnitRepository $units) {}

    /** @return Collection<int, Province> */
    public function provinceOptions(): Collection
    {
        return $this->units->provinceOptions();
    }

    /** @return Collection<int, Ward> */
    public function wardOptions(?int $provinceId): Collection
    {
        if ($provinceId === null) {
            return new Collection;
        }

        return $this->units->wardOptions($provinceId);
    }

    /**
     * Validate a selected administrative-unit pair and keep legacy text columns
     * synchronized while old imports/reports are being migrated to foreign keys.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeAddressPayload(array $data): array
    {
        if (! array_key_exists('province_id', $data)) {
            return $data;
        }

        $provinceId = $this->nullableId($data['province_id']);
        $wardId = $this->nullableId($data['ward_id'] ?? null);

        if ($provinceId === null) {
            if ($wardId !== null) {
                throw ValidationException::withMessages([
                    'wardId' => 'Vui lòng chọn Tỉnh/Thành phố trước khi chọn Phường/Xã.',
                ]);
            }

            return [
                ...$data,
                'province_id' => null,
                'ward_id' => null,
                'province' => null,
                'city' => null,
            ];
        }

        $province = $this->units->findActiveProvince($provinceId);
        if (! $province instanceof Province) {
            throw ValidationException::withMessages([
                'provinceId' => 'Tỉnh/Thành phố đã chọn không tồn tại hoặc đã ngừng sử dụng.',
            ]);
        }

        $ward = null;
        if ($wardId !== null) {
            $ward = $this->units->findActiveWardInProvince($wardId, $provinceId);
            if (! $ward instanceof Ward) {
                throw ValidationException::withMessages([
                    'wardId' => 'Phường/Xã không thuộc Tỉnh/Thành phố đã chọn.',
                ]);
            }
        }

        return [
            ...$data,
            'province_id' => $province->getKey(),
            'ward_id' => $ward?->getKey(),
            'province' => $province->full_name,
            'city' => $ward?->full_name,
            'country' => 'Việt Nam',
        ];
    }

    /**
     * @return array{provinces: int, wards: int, backfilled: int}
     *
     * @throws JsonException
     */
    public function importFromJson(string $path, bool $backfill = true): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Không đọc được file dữ liệu địa giới: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Không thể đọc file dữ liệu địa giới: {$path}");
        }

        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ! array_is_list($decoded)) {
            throw new RuntimeException('JSON địa giới phải là một danh sách Tỉnh/Thành phố.');
        }

        return DB::transaction(function () use ($decoded, $backfill): array {
            $now = now();
            $provinceRows = [];

            foreach ($decoded as $province) {
                if (! is_array($province) || ($province['Type'] ?? null) !== 'province') {
                    throw new RuntimeException('JSON chứa bản ghi Tỉnh/Thành phố không hợp lệ.');
                }

                $provinceRows[] = $this->mapUnit($province, $now);
            }

            $this->units->upsertProvinces($provinceRows);
            $provinceIds = $this->units->provinceIdsByCode();
            $wardRows = [];

            foreach ($decoded as $province) {
                $provinceCode = (string) ($province['Code'] ?? '');
                $provinceId = $provinceIds[$provinceCode] ?? null;

                if ($provinceId === null) {
                    throw new RuntimeException("Không tìm thấy Tỉnh/Thành phố mã {$provinceCode} sau khi import.");
                }

                $wards = $province['Wards'] ?? [];
                if (! is_array($wards) || ! array_is_list($wards)) {
                    throw new RuntimeException("Danh sách Phường/Xã của mã {$provinceCode} không hợp lệ.");
                }

                foreach ($wards as $ward) {
                    if (! is_array($ward) || ($ward['Type'] ?? null) !== 'ward') {
                        throw new RuntimeException("JSON chứa Phường/Xã không hợp lệ trong tỉnh mã {$provinceCode}.");
                    }

                    if ((string) ($ward['ProvinceCode'] ?? '') !== $provinceCode) {
                        throw new RuntimeException('Mã Tỉnh/Thành phố của Phường/Xã không khớp cây dữ liệu.');
                    }

                    $wardRows[] = [
                        ...$this->mapUnit($ward, $now),
                        'province_id' => $provinceId,
                    ];
                }
            }

            foreach (array_chunk($wardRows, 500) as $chunk) {
                $this->units->upsertWards($chunk);
            }

            return [
                'provinces' => count($provinceRows),
                'wards' => count($wardRows),
                'backfilled' => $backfill ? $this->backfillLegacyAddresses() : 0,
            ];
        });
    }

    public function backfillLegacyAddresses(): int
    {
        $provinceMap = [];
        foreach ($this->units->allProvinces() as $province) {
            foreach ([$province->name, $province->full_name, $province->code_name] as $alias) {
                $provinceMap[$this->normalizeName((string) $alias)] = (int) $province->getKey();
            }
        }

        /** @var array<int, array<string, int>> $wardMaps */
        $wardMaps = [];
        /** @var array<int, array<string, true>> $ambiguousWardAliases */
        $ambiguousWardAliases = [];
        foreach ($this->units->allWards() as $ward) {
            foreach ([$ward->name, $ward->full_name, $ward->code_name] as $alias) {
                $provinceId = (int) $ward->province_id;
                $normalizedAlias = $this->normalizeName((string) $alias);
                $existingId = $wardMaps[$provinceId][$normalizedAlias] ?? null;

                if ($existingId !== null && $existingId !== (int) $ward->getKey()) {
                    unset($wardMaps[$provinceId][$normalizedAlias]);
                    $ambiguousWardAliases[$provinceId][$normalizedAlias] = true;

                    continue;
                }

                if (! isset($ambiguousWardAliases[$provinceId][$normalizedAlias])) {
                    $wardMaps[$provinceId][$normalizedAlias] = (int) $ward->getKey();
                }
            }
        }

        $updated = 0;
        foreach ([Lead::class, Company::class, Contact::class] as $modelClass) {
            $modelClass::query()
                ->whereNull('province_id')
                ->select(['id', 'city', 'province'])
                ->chunkById(200, function (Collection $records) use ($provinceMap, $wardMaps, &$updated): void {
                    foreach ($records as $record) {
                        $provinceKey = $this->normalizeName((string) $record->province);
                        $provinceId = $provinceMap[$provinceKey] ?? null;

                        if ($provinceId === null) {
                            $cityAsProvinceKey = $this->normalizeName((string) $record->city);
                            $provinceId = $provinceMap[$cityAsProvinceKey] ?? null;
                        }

                        if ($provinceId === null) {
                            continue;
                        }

                        $cityKey = $this->normalizeName((string) $record->city);
                        $wardId = $wardMaps[$provinceId][$cityKey] ?? null;

                        $record->forceFill([
                            'province_id' => $provinceId,
                            'ward_id' => $wardId,
                        ])->saveQuietly();

                        $updated++;
                    }
                });
        }

        return $updated;
    }

    /** @return array<string, mixed> */
    private function mapUnit(array $unit, mixed $now): array
    {
        $code = trim((string) ($unit['Code'] ?? ''));
        $name = trim((string) ($unit['Name'] ?? ''));
        $fullName = trim((string) ($unit['FullName'] ?? ''));
        $codeName = trim((string) ($unit['CodeName'] ?? ''));

        if ($code === '' || $name === '' || $fullName === '' || $codeName === '') {
            throw new RuntimeException('Đơn vị hành chính thiếu code, name, full name hoặc code name.');
        }

        return [
            'code' => $code,
            'name' => $name,
            'name_en' => $this->nullableString($unit['NameEn'] ?? null),
            'full_name' => $fullName,
            'full_name_en' => $this->nullableString($unit['FullNameEn'] ?? null),
            'code_name' => $codeName,
            'administrative_unit_id' => isset($unit['AdministrativeUnitId'])
                ? (int) $unit['AdministrativeUnitId']
                : null,
            'administrative_unit_short_name' => $this->nullableString($unit['AdministrativeUnitShortName'] ?? null),
            'administrative_unit_full_name' => $this->nullableString($unit['AdministrativeUnitFullName'] ?? null),
            'administrative_unit_short_name_en' => $this->nullableString($unit['AdministrativeUnitShortNameEn'] ?? null),
            'administrative_unit_full_name_en' => $this->nullableString($unit['AdministrativeUnitFullNameEn'] ?? null),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeName(string $value): string
    {
        $normalized = Str::lower(Str::ascii(trim($value)));
        $normalized = str_replace('_', ' ', $normalized);
        $normalized = (string) preg_replace('/[^a-z0-9 ]+/', ' ', $normalized);
        $normalized = (string) preg_replace('/\s+/', ' ', $normalized);
        $normalized = (string) preg_replace('/^(thanh pho|tinh|tp|phuong|xa|dac khu)\s+/', '', trim($normalized));

        return trim($normalized);
    }
}
