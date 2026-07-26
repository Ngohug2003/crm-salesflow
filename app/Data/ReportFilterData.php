<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Carbon;

final readonly class ReportFilterData
{
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
        if ($this->datePreset === 'today') {
            return [now()->startOfDay(), now()->endOfDay()];
        }

        if ($this->datePreset === 'this_week') {
            return [now()->startOfWeek(), now()->endOfWeek()];
        }

        if ($this->datePreset === 'this_month') {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        if ($this->datePreset === 'this_quarter') {
            return [now()->startOfQuarter(), now()->endOfQuarter()];
        }

        if ($this->datePreset === 'this_year') {
            return [now()->startOfYear(), now()->endOfYear()];
        }

        if ($this->startDate !== null && $this->startDate !== '') {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = ($this->endDate !== null && $this->endDate !== '')
                ? Carbon::parse($this->endDate)->endOfDay()
                : now()->endOfDay();

            return [$start, $end];
        }

        // Mặc định tháng hiện tại nếu không chọn preset hoặc range
        return [now()->startOfMonth(), now()->endOfMonth()];
    }
}
