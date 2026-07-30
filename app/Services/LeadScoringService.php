<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Activity;
use App\Models\Lead;
use Illuminate\Support\Carbon;

final class LeadScoringService
{
    /**
     * Calculate total score for a given Lead based on configured rules.
     */
    public function calculateScore(Lead $lead): int
    {
        $score = 0;

        // 1. Profile Completeness (max 50 points)
        if (! empty($lead->email)) {
            $score += config('lead_scoring.profile_completeness.has_email', 10);
        }
        if (! empty($lead->phone) || ! empty($lead->secondary_phone)) {
            $score += config('lead_scoring.profile_completeness.has_phone', 10);
        }
        if (! empty($lead->company_name)) {
            $score += config('lead_scoring.profile_completeness.has_company', 10);
        }
        if (! empty($lead->job_title)) {
            $score += config('lead_scoring.profile_completeness.has_job_title', 5);
        }
        if (! empty($lead->address) || ! empty($lead->city) || ! empty($lead->province)) {
            $score += config('lead_scoring.profile_completeness.has_address_or_city', 5);
        }
        if ($lead->estimated_value !== null && (float) $lead->estimated_value > 0) {
            $score += config('lead_scoring.profile_completeness.has_estimated_value', 10);
        }

        // 2. Status Points
        /** @var LeadStatus|null $status */
        $status = $lead->status;
        $statusValue = $status !== null ? $status->value : 'new';
        $statusKey = match ($statusValue) {
            'qualified' => 'qualified',
            'contacted' => 'in_progress',
            'new' => 'new',
            'unqualified' => 'unqualified',
            'lost' => 'lost',
            default => 'new',
        };
        $score += config("lead_scoring.status_points.{$statusKey}", 0);

        // 3. Priority Points
        /** @var LeadPriority|null $priority */
        $priority = $lead->priority;
        $priorityValue = $priority !== null ? $priority->value : 'medium';
        $priorityKey = match ($priorityValue) {
            'urgent', 'high' => 'high',
            'medium' => 'medium',
            default => 'low',
        };
        $score += config("lead_scoring.priority_points.{$priorityKey}", 0);

        // 4. Engagement & Assignment
        if ($lead->owner_id !== null) {
            $score += config('lead_scoring.engagement.has_owner', 10);
        }

        // Check recent activities (within last 7 days)
        /** @var Activity|null $latestActivity */
        $latestActivity = Activity::query()
            ->where('subject_type', Lead::class)
            ->where('subject_id', $lead->id)
            ->latest('created_at')
            ->first();

        if ($latestActivity !== null && $latestActivity->created_at->diffInDays(Carbon::now()) <= 7) {
            $score += config('lead_scoring.engagement.recent_activity_bonus', 15);
        }

        // 5. Stale Penalties
        $lastTouchDate = $latestActivity !== null ? $latestActivity->created_at : ($lead->created_at ?? Carbon::now());
        $daysInactive = (int) $lastTouchDate->diffInDays(Carbon::now());

        if ($daysInactive > 30) {
            $score += config('lead_scoring.stale_penalties.stale_30_days', -30);
        } elseif ($daysInactive > 14) {
            $score += config('lead_scoring.stale_penalties.stale_14_days', -15);
        }

        return max(0, $score);
    }

    /**
     * Recalculate score and save into lead model.
     */
    public function recalculateAndSave(Lead $lead): int
    {
        $newScore = $this->calculateScore($lead);
        if ($lead->score !== $newScore) {
            Lead::withoutEvents(function () use ($lead, $newScore): void {
                $lead->update(['score' => $newScore]);
            });
        }

        return $newScore;
    }

    /**
     * Recalculate score for all leads.
     */
    public function recalculateAll(): int
    {
        $count = 0;
        Lead::query()->chunk(200, function ($leads) use (&$count): void {
            foreach ($leads as $lead) {
                $this->recalculateAndSave($lead);
                $count++;
            }
        });

        return $count;
    }
}
