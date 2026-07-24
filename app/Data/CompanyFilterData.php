<?php

declare(strict_types=1);

namespace App\Data;

final readonly class CompanyFilterData
{
    public function __construct(
        public string $search = '',
        public ?string $industry = null,
        public ?string $companySize = null,
        public ?int $ownerId = null,
        public ?int $departmentId = null,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
    ) {}
}
