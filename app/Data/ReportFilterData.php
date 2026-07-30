<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Carbon;
use Throwable;

final readonly class ReportFilterData
{
    /** @var list<string> */
    private const array DATE_PRESETS = [
        'today',
        'this_week',
        'this_month',
        'this_quarter',
        'this_year',
        'custom',
    ];

    public function __construct(
        public ?string $datePreset = 'this_month',
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?int $departmentId = null,
        public ?int $userId = null,
        public ?int $pipelineId = null,
    ) {}

    /**
     * Resolve date range as [Carbon $start, Carbon $end]
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveDateRange(): array
    {
        $preset = in_array($this->datePreset, self::DATE_PRESETS, true)
            ? $this->datePreset
            : 'this_month';

        return match ($preset) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => $this->resolveCustomDateRange(),
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    /**
     * Canonical payload for report cache keys.
     *
     * @return array<string, int|string|null>
     */
    public function cachePayload(): array
    {
        [$start, $end] = $this->resolveDateRange();

        return [
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'department_id' => $this->departmentId,
            'user_id' => $this->userId,
            'pipeline_id' => $this->pipelineId,
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveCustomDateRange(): array
    {
        try {
            $start = $this->parseDate($this->startDate)?->startOfDay();
            $end = $this->parseDate($this->endDate)?->endOfDay();
        } catch (Throwable) {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        if ($start === null && $end === null) {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        if ($start === null) {
            $start = $end->copy()->startOfDay();
        }
        if ($end === null) {
            $end = now()->endOfDay();
        }

        if ($start->greaterThan($end)) {
            return [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function parseDate(?string $date): ?Carbon
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        return Carbon::createFromFormat('!Y-m-d', trim($date));
    }
}
