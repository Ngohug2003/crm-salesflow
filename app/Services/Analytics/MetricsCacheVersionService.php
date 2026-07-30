<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;

final class MetricsCacheVersionService
{
    private const string KEY = 'crm_metrics:version';

    public function current(): int
    {
        return max(1, (int) Cache::get(self::KEY, 1));
    }

    public function bump(): int
    {
        $next = $this->current() + 1;
        Cache::forever(self::KEY, $next);

        return $next;
    }
}
