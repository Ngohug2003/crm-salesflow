<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\AdministrativeUnitService;
use Illuminate\Database\Seeder;

final class VietnamAdministrativeUnitSeeder extends Seeder
{
    public function run(): void
    {
        /** @var AdministrativeUnitService $service */
        $service = app(AdministrativeUnitService::class);
        $result = $service->importFromJson(
            database_path('data/vietnam_administrative_units.json'),
        );

        $this->command?->info(
            "Đã đồng bộ {$result['provinces']} tỉnh/thành, {$result['wards']} phường/xã; "
            ."backfill {$result['backfilled']} bản ghi CRM.",
        );
    }
}
