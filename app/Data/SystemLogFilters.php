<?php

declare(strict_types=1);

namespace App\Data;

final readonly class SystemLogFilters
{
    public string $search;

    public string $level;

    public string $module;

    public int $limit;

    public function __construct(string $search = '', string $level = 'all', string $module = 'all', int $limit = 100)
    {
        $this->search = mb_strtolower(trim($search));
        $this->level = strtoupper(trim($level)) ?: 'ALL';
        $this->module = mb_strtolower(trim($module)) ?: 'all';
        $this->limit = in_array($limit, [50, 100, 200], true) ? $limit : 100;
    }
}
