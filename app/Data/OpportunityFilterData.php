<?php

declare(strict_types=1);

namespace App\Data;

final readonly class OpportunityFilterData
{
    public function __construct(
        public ?string $search = null,
        public ?int $pipelineId = null,
        public ?int $stageId = null,
        public ?int $companyId = null,
        public ?int $contactId = null,
        public ?int $ownerId = null,
        public ?int $departmentId = null,
        public ?string $status = null, // 'open', 'won', 'lost'
        public ?string $forecastCategory = null,
        public ?string $expectedCloseFrom = null,
        public ?string $expectedCloseTo = null,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
    ) {}
}
