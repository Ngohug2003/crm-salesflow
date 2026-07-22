<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use Carbon\CarbonImmutable;

final readonly class LeadFilterData
{
    public function __construct(
        public string $search = '',
        public ?LeadStatus $status = null,
        public ?LeadPriority $priority = null,
        public ?int $sourceId = null,
        public ?int $tagId = null,
        public ?int $ownerId = null,
        public ?int $departmentId = null,
        public ?CarbonImmutable $createdFrom = null,
        public ?CarbonImmutable $createdTo = null,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
    ) {}
}
