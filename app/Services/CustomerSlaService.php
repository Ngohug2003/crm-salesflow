<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class CustomerSlaService
{
    /**
     * Get default SLA hours target for subject type.
     */
    public function getTargetSlaHours(Model $subject): int
    {
        return match ($subject::class) {
            Lead::class => 24,         // 24 hours (1 day) for Leads
            Opportunity::class => 72,  // 72 hours (3 days) for Opportunities
            Company::class, Contact::class => 168, // 168 hours (7 days) for Customers
            default => 72,
        };
    }

    /**
     * Get last interaction datetime for subject (from latest Activity, Task or subject created_at).
     */
    public function getLastInteractionAt(Model $subject): Carbon
    {
        $subjectClass = $subject::class;
        $subjectId = $subject->getKey();

        /** @var string|null $latestActivityAt */
        $latestActivityAt = Activity::query()
            ->where('subject_type', $subjectClass)
            ->where('subject_id', $subjectId)
            ->max('created_at');

        /** @var string|null $latestTaskAt */
        $latestTaskAt = Task::query()
            ->where('subject_type', $subjectClass)
            ->where('subject_id', $subjectId)
            ->max('created_at');

        /** @var \DateTimeInterface|string|null $createdAt */
        $createdAt = $subject->getAttribute('created_at');
        $createdAtIso = null;
        if ($createdAt instanceof \DateTimeInterface) {
            $createdAtIso = $createdAt->format(\DateTimeInterface::ATOM);
        } elseif (is_string($createdAt) && $createdAt !== '') {
            $createdAtIso = $createdAt;
        }

        $timestamps = array_filter([
            $latestActivityAt,
            $latestTaskAt,
            $createdAtIso,
        ]);

        if ($timestamps === []) {
            return Carbon::now();
        }

        $latestIso = max($timestamps);

        return Carbon::parse($latestIso);
    }

    /**
     * Calculate SLA status for a subject model.
     *
     * @return array{
     *     status: 'on_track'|'warning'|'breached',
     *     label: string,
     *     color: string,
     *     hours_since_interaction: int,
     *     target_hours: int,
     *     due_at: Carbon,
     *     last_interaction_at: Carbon
     * }
     */
    public function getSlaInfo(Model $subject): array
    {
        $lastInteractionAt = $this->getLastInteractionAt($subject);
        $targetHours = $this->getTargetSlaHours($subject);
        $dueAt = $lastInteractionAt->copy()->addHours($targetHours);

        $now = Carbon::now();
        $hoursSince = (int) $lastInteractionAt->diffInHours($now, false);
        $hoursRemaining = (int) $now->diffInHours($dueAt, false);

        $warningThresholdHours = min(24, max(4, (int) ($targetHours / 3)));

        if ($now->greaterThan($dueAt)) {
            $status = 'breached';
            $label = 'Vi phạm SLA';
            $color = 'red';
        } elseif ($hoursRemaining <= $warningThresholdHours) {
            $status = 'warning';
            $label = 'Sắp quá hạn SLA';
            $color = 'amber';
        } else {
            $status = 'on_track';
            $label = 'Đúng hạn SLA';
            $color = 'emerald';
        }

        return [
            'status' => $status,
            'label' => $label,
            'color' => $color,
            'hours_since_interaction' => max(0, $hoursSince),
            'target_hours' => $targetHours,
            'due_at' => $dueAt,
            'last_interaction_at' => $lastInteractionAt,
        ];
    }
}
