<?php

declare(strict_types=1);

namespace App\Data;

final readonly class TaskFilterData
{
    public function __construct(
        public ?string $search = null,
        public ?string $status = null,
        public ?string $priority = null,
        public ?int $assignedTo = null,
        public ?string $subjectType = null,
        public ?int $subjectId = null,
        public ?bool $overdue = null,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
    ) {}
}
