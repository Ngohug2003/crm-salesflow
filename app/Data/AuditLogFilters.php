<?php

declare(strict_types=1);

namespace App\Data;

final readonly class AuditLogFilters
{
    public function __construct(
        public string $search = '',
        public string $module = 'all',
        public string $event = 'all',
        public string $actor = 'all',
        public string $dateFrom = '',
        public string $dateTo = '',
        public string $requestId = '',
    ) {}
}
