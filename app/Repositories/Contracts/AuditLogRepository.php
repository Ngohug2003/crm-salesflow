<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\AuditLogFilters;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

interface AuditLogRepository
{
    /** @return LengthAwarePaginator<int, Activity> */
    public function paginate(AuditLogFilters $filters, int $perPage = 20): LengthAwarePaginator;

    /** @return list<string> */
    public function logNames(): array;

    /** @return list<string> */
    public function events(): array;

    /** @return Collection<int, User> */
    public function actors(): Collection;
}
