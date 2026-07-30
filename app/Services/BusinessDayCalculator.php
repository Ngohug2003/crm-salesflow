<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class BusinessDayCalculator
{
    public function addBusinessDays(CarbonInterface $from, int $days): CarbonImmutable
    {
        $deadline = CarbonImmutable::instance($from)->setTimezone(config('crm.display_timezone'));
        $remaining = max(0, $days);

        while ($remaining > 0) {
            $deadline = $deadline->addDay();
            if (! $deadline->isWeekend()) {
                $remaining--;
            }
        }

        return $deadline;
    }
}
