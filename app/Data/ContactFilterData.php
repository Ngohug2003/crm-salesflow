<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ContactFilterData
{
    public function __construct(
        public string $search = '',
        public ?int $companyId = null,
        public ?bool $isPrimary = null,
        public ?int $ownerId = null,
        public ?int $departmentId = null,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
    ) {}
}
