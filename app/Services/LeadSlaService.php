<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\LeadNote;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

final class LeadSlaService
{
    /**
     * Calculate First Touch SLA deadline in Asia/Ho_Chi_Minh timezone.
     * Standard business hours: Mon-Fri, 08:00 to 17:30 (9.5 hours per business day).
     */
    public function calculateSlaDeadline(?Carbon $fromTime = null, int $hours = 2): Carbon
    {
        $current = CarbonImmutable::parse($fromTime ?? now('Asia/Ho_Chi_Minh'))->setTimezone('Asia/Ho_Chi_Minh');
        $remainingMinutes = $hours * 60;

        while ($remainingMinutes > 0) {
            // If weekend (Saturday / Sunday), advance to Monday 08:00
            if ($current->isWeekend()) {
                $current = $current->next(Carbon::MONDAY)->setTime(8, 0);
            }

            $startOfDay = $current->copy()->setTime(8, 0);
            $endOfDay = $current->copy()->setTime(17, 30);

            if ($current->lt($startOfDay)) {
                $current = $startOfDay;
            } elseif ($current->gte($endOfDay)) {
                $current = $current->addDay()->setTime(8, 0);

                continue;
            }

            $minutesAvailableToday = $current->diffInMinutes($endOfDay);

            if ($remainingMinutes <= $minutesAvailableToday) {
                $current = $current->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                $remainingMinutes -= $minutesAvailableToday;
                $current = $current->addDay()->setTime(8, 0);
            }
        }

        return $current->toMutable();
    }

    /**
     * Check if a lead has a valid First Touch (Activity or Note) and satisfy SLA.
     */
    public function checkAndSatisfySla(Lead $lead): bool
    {
        if ($lead->sla_satisfied_at !== null) {
            return true;
        }

        $hasActivity = Activity::query()
            ->where('subject_type', Lead::class)
            ->where('subject_id', $lead->id)
            ->exists();

        $hasNote = LeadNote::query()
            ->where('lead_id', $lead->id)
            ->exists();

        if ($hasActivity || $hasNote) {
            $lead->forceFill([
                'sla_satisfied_at' => now('Asia/Ho_Chi_Minh'),
                'is_sla_overdue' => false,
            ])->save();

            return true;
        }

        return false;
    }
}
