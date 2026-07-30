<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Opportunity;
use App\Models\OpportunityRiskAcknowledgement;
use App\Models\OpportunityRiskFactor;
use App\Models\OpportunityRiskSnapshot;
use App\Models\Quote;
use App\Models\Task;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class OpportunityRiskService
{
    /**
     * Evaluate risk score and factors for a single Opportunity.
     */
    public function evaluateOpportunity(Opportunity $opportunity): OpportunityRiskSnapshot
    {
        return DB::transaction(function () use ($opportunity): OpportunityRiskSnapshot {
            $factorsData = [];
            $totalScore = 0;

            // Factor 1: No recent activity (> 7 days)
            $lastActivity = Activity::query()
                ->where('subject_type', Opportunity::class)
                ->where('subject_id', $opportunity->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $lastActivityDate = $lastActivity !== null
                ? Carbon::parse($lastActivity->created_at)
                : Carbon::parse($opportunity->created_at);
            $daysSinceActivity = (int) $lastActivityDate->diffInDays(now());

            if ($daysSinceActivity > 7) {
                $score = 25;
                $totalScore += $score;
                $factorsData[] = [
                    'code' => 'NO_ACTIVITY_7_DAYS',
                    'points' => $score,
                    'title' => 'Không có tương tác mới (> 7 ngày)',
                    'recommended_action' => 'Tạo cuộc gọi/cuộc họp hoặc ghi chú hoạt động mới với khách hàng.',
                    'details' => ['days_inactive' => $daysSinceActivity],
                ];
            }

            // Factor 2: Stagnant stage (> 14 days)
            $latestStageChange = $opportunity->stageHistories()->first();
            $stageDate = $latestStageChange !== null
                ? Carbon::parse($latestStageChange->created_at)
                : Carbon::parse($opportunity->updated_at);
            $daysInStage = (int) $stageDate->diffInDays(now());

            if ($daysInStage > 14) {
                $score = 20;
                $totalScore += $score;
                $factorsData[] = [
                    'code' => 'STAGNANT_STAGE_14_DAYS',
                    'points' => $score,
                    'title' => 'Cơ hội đứng yên tại giai đoạn (> 14 ngày)',
                    'recommended_action' => 'Đánh giá exit criteria và đẩy nhanh tiến độ sang giai đoạn tiếp theo.',
                    'details' => ['days_in_stage' => $daysInStage, 'stage' => $opportunity->stage?->name],
                ];
            }

            // Factor 3: Close date risk (past expected close date or <= 3 days away)
            if ($opportunity->expected_close_date !== null) {
                $closeDate = Carbon::parse($opportunity->expected_close_date)->startOfDay();
                $today = now()->startOfDay();

                if ($closeDate->lt($today)) {
                    $score = 25;
                    $totalScore += $score;
                    $daysOverdue = (int) $closeDate->diffInDays($today);
                    $factorsData[] = [
                        'code' => 'PAST_EXPECTED_CLOSE_DATE',
                        'points' => $score,
                        'title' => 'Đã quá ngày chốt dự kiến',
                        'recommended_action' => 'Cập nhật lại ngày chốt dự kiến hoặc hoàn tất chốt đơn.',
                        'details' => ['days_overdue' => $daysOverdue, 'close_date' => $closeDate->format('Y-m-d')],
                    ];
                } elseif ($today->diffInDays($closeDate) <= 3) {
                    $score = 15;
                    $totalScore += $score;
                    $factorsData[] = [
                        'code' => 'NEAR_EXPECTED_CLOSE_DATE',
                        'points' => $score,
                        'title' => 'Gần đến ngày chốt dự kiến (<= 3 ngày)',
                        'recommended_action' => 'Kiểm tra lại báo giá và chuẩn bị hợp đồng chốt Deal.',
                        'details' => ['close_date' => $closeDate->format('Y-m-d')],
                    ];
                }
            }

            // Factor 4: Rejected or expired Quote
            $hasRejectedOrExpiredQuote = Quote::query()
                ->where('opportunity_id', $opportunity->id)
                ->whereIn('status', ['rejected', 'expired'])
                ->exists();

            if ($hasRejectedOrExpiredQuote) {
                $score = 20;
                $totalScore += $score;
                $factorsData[] = [
                    'code' => 'REJECTED_OR_EXPIRED_QUOTE',
                    'points' => $score,
                    'title' => 'Báo giá bị từ chối hoặc hết hạn',
                    'recommended_action' => 'Tạo Báo giá mới điều chỉnh chiết khấu hoặc liên hệ làm rõ lý do từ chối.',
                    'details' => ['has_rejected_quote' => true],
                ];
            }

            // Factor 5: Overdue uncompleted Tasks
            $hasOverdueTasks = Task::query()
                ->where('subject_type', Opportunity::class)
                ->where('subject_id', $opportunity->id)
                ->where('status', '!=', 'done')
                ->whereNull('completed_at')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->startOfDay())
                ->exists();

            if ($hasOverdueTasks) {
                $score = 15;
                $totalScore += $score;
                $factorsData[] = [
                    'code' => 'OVERDUE_TASKS',
                    'points' => $score,
                    'title' => 'Có công việc quá hạn chưa hoàn thành',
                    'recommended_action' => 'Hoàn thành các công việc quá hạn hoặc gia hạn ngày xử lý.',
                    'details' => ['has_overdue_tasks' => true],
                ];
            }

            // Factor 6: Missing Primary Contact
            if ($opportunity->contact_id === null) {
                $score = 15;
                $totalScore += $score;
                $factorsData[] = [
                    'code' => 'MISSING_PRIMARY_CONTACT',
                    'points' => $score,
                    'title' => 'Chưa có Người liên hệ chính (Primary Contact)',
                    'recommended_action' => 'Cập nhật Người liên hệ chính cho Cơ hội bán hàng.',
                    'details' => ['missing_contact' => true],
                ];
            }

            $finalScore = min(100, $totalScore);
            $riskLevel = match (true) {
                $finalScore >= 76 => 'critical',
                $finalScore >= 51 => 'high',
                $finalScore >= 26 => 'medium',
                default => 'low',
            };

            $ruleVersion = 'v1';
            $evaluationBucket = now()->startOfHour();

            $summary = count($factorsData) > 0
                ? implode('; ', array_column($factorsData, 'title'))
                : 'An toàn - Không phát hiện tín hiệu rủi ro.';

            $existingSnapshot = OpportunityRiskSnapshot::query()
                ->where('opportunity_id', $opportunity->id)
                ->where('rule_version', $ruleVersion)
                ->where('evaluation_bucket', $evaluationBucket)
                ->first();

            if ($existingSnapshot !== null) {
                OpportunityRiskSnapshot::query()
                    ->where('opportunity_id', $opportunity->id)
                    ->where('id', '!=', $existingSnapshot->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                $existingSnapshot->update([
                    'score' => $finalScore,
                    'level' => $riskLevel,
                    'summary' => mb_substr($summary, 0, 500),
                    'is_current' => true,
                    'evaluated_at' => now(),
                ]);

                $snapshot = $existingSnapshot;
                $snapshot->factors()->delete();
            } else {
                OpportunityRiskSnapshot::query()
                    ->where('opportunity_id', $opportunity->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                $snapshot = OpportunityRiskSnapshot::query()->create([
                    'opportunity_id' => $opportunity->id,
                    'rule_version' => $ruleVersion,
                    'evaluation_bucket' => $evaluationBucket,
                    'score' => $finalScore,
                    'level' => $riskLevel,
                    'summary' => mb_substr($summary, 0, 500),
                    'is_current' => true,
                    'evaluated_at' => now(),
                ]);
            }

            foreach ($factorsData as $f) {
                OpportunityRiskFactor::query()->create([
                    'opportunity_risk_snapshot_id' => $snapshot->id,
                    'code' => $f['code'],
                    'title' => $f['title'],
                    'points' => $f['points'],
                    'recommended_action' => $f['recommended_action'],
                    'details' => $f['details'],
                ]);
            }

            return $snapshot;
        });
    }

    /**
     * Batch evaluate all active open opportunities.
     */
    public function evaluateAllActive(): int
    {
        $count = 0;

        Opportunity::query()
            ->where('is_won', false)
            ->where('is_lost', false)
            ->chunkById(100, function ($opportunities) use (&$count): void {
                foreach ($opportunities as $opp) {
                    $this->evaluateOpportunity($opp);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Batch evaluate active open opportunities within user's data scope.
     */
    public function evaluateForUser(User $actor, DataScopeService $dataScope): int
    {
        $count = 0;

        $query = Opportunity::query()
            ->where('is_won', false)
            ->where('is_lost', false);

        $dataScope->apply($query, $actor);

        $query->chunkById(100, function ($opportunities) use (&$count): void {
            foreach ($opportunities as $opp) {
                $this->evaluateOpportunity($opp);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Acknowledge or resolve an opportunity risk.
     */
    public function acknowledgeRisk(User $user, Opportunity $opportunity, string $status, ?string $notes = null): OpportunityRiskAcknowledgement
    {
        $snapshot = $opportunity->currentRiskSnapshot ?? $this->evaluateOpportunity($opportunity);

        return OpportunityRiskAcknowledgement::query()->create([
            'opportunity_risk_snapshot_id' => $snapshot->id,
            'user_id' => $user->id,
            'status' => in_array($status, ['acknowledged', 'in_progress', 'resolved'], true) ? $status : 'acknowledged',
            'note' => $notes,
            'acted_at' => now(),
        ]);
    }
}
