<?php

declare(strict_types=1);

namespace App\Data;

final readonly class UserListFilters
{
    public function __construct(
        public string $search = '',
        public string $department = 'all',
        public string $role = 'all',
        public string $status = 'all',
    ) {}
}
