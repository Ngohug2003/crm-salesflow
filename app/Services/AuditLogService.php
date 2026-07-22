<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\AuditLogFilters;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

final readonly class AuditLogService
{
    public function __construct(private AuditLogRepository $auditLogs) {}

    /** @return LengthAwarePaginator<int, Activity> */
    public function paginate(AuditLogFilters $filters): LengthAwarePaginator
    {
        return $this->auditLogs->paginate($filters);
    }

    /** @return array{modules: list<string>, events: list<string>, actors: Collection<int, User>} */
    public function options(): array
    {
        return [
            'modules' => $this->auditLogs->logNames(),
            'events' => $this->auditLogs->events(),
            'actors' => $this->auditLogs->actors(),
        ];
    }
}
