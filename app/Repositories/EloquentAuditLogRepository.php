<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\AuditLogFilters;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepository;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

final class EloquentAuditLogRepository implements AuditLogRepository
{
    public function paginate(AuditLogFilters $filters, int $perPage = 20): LengthAwarePaginator
    {
        $search = trim($filters->search);
        $dateFrom = $this->dateBoundary($filters->dateFrom, endOfDay: false);
        $dateTo = $this->dateBoundary($filters->dateTo, endOfDay: true);

        return Activity::query()
            ->with('causer')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('description', "%{$search}%", caseSensitive: false)
                        ->orWhereLike('log_name', "%{$search}%", caseSensitive: false)
                        ->orWhereHasMorph(
                            'causer',
                            [User::class],
                            fn (Builder $query): Builder => $query
                                ->whereLike('name', "%{$search}%", caseSensitive: false)
                                ->orWhereLike('email', "%{$search}%", caseSensitive: false),
                        );
                });
            })
            ->when($filters->module !== 'all', fn (Builder $query): Builder => $query->where('log_name', $filters->module))
            ->when($filters->event !== 'all', fn (Builder $query): Builder => $query->where('event', $filters->event))
            ->when(ctype_digit($filters->actor), fn (Builder $query): Builder => $query->where('causer_id', (int) $filters->actor))
            ->when($dateFrom !== null, fn (Builder $query): Builder => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query): Builder => $query->where('created_at', '<=', $dateTo))
            ->latest('id')
            ->paginate($perPage);
    }

    public function logNames(): array
    {
        return Activity::query()->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name')->all();
    }

    public function events(): array
    {
        return Activity::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event')->all();
    }

    public function actors(): Collection
    {
        $ids = Activity::query()
            ->where('causer_type', User::class)
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id');

        return User::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'email']);
    }

    private function dateBoundary(string $value, bool $endOfDay): ?CarbonImmutable
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value,
            new DateTimeZone((string) config('crm.display_timezone')),
        );

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        $localBoundary = $endOfDay
            ? CarbonImmutable::instance($date)->endOfDay()
            : CarbonImmutable::instance($date)->startOfDay();

        return $localBoundary;
    }
}
