<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AdministrativeUnitService;
use Illuminate\Console\Command;
use Throwable;

final class ImportVietnamAdministrativeUnitsCommand extends Command
{
    protected $signature = 'administrative-units:import
                            {path? : Đường dẫn JSON, mặc định database/data/vietnam_administrative_units.json}
                            {--no-backfill : Không ánh xạ dữ liệu địa chỉ CRM cũ}';

    protected $description = 'Import idempotent danh mục Tỉnh/Thành phố và Phường/Xã Việt Nam';

    public function handle(AdministrativeUnitService $service): int
    {
        $path = $this->argument('path');
        $resolvedPath = is_string($path) && $path !== ''
            ? $path
            : database_path('data/vietnam_administrative_units.json');

        try {
            $result = $service->importFromJson(
                $resolvedPath,
                ! (bool) $this->option('no-backfill'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Đã import {$result['provinces']} tỉnh/thành phố.");
        $this->info("Đã import {$result['wards']} phường/xã.");
        $this->info("Đã backfill {$result['backfilled']} bản ghi CRM cũ.");

        return self::SUCCESS;
    }
}
