<?php

declare(strict_types=1);

namespace App\Data;

final readonly class PipelineFilterData
{
    public function __construct(
        public ?string $search = null,
        public ?bool $isActive = null,
        public ?bool $isDefault = null,
        public ?int $ownerId = null,
        public ?int $departmentId = null,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
    ) {}
}
