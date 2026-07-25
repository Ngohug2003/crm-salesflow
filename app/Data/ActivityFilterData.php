<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ActivityType;

final readonly class ActivityFilterData
{
    public function __construct(
        public ?ActivityType $activityType = null,
        public ?string $search = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}
}
