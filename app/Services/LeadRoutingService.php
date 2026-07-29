<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadAssignmentHistory;
use App\Models\LeadRoutingCursor;
use App\Models\LeadRoutingExecution;
use App\Models\LeadRoutingRule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class LeadRoutingService
{
    public function __construct(
        private readonly LeadSlaService $slaService
    ) {}

    /**
     * Route a lead automatically using active rules and round-robin cursor.
     */
    public function routeLead(Lead $lead, ?User $executor = null, ?array $excludeUserIds = null): LeadRoutingExecution
    {
        return DB::transaction(function () use ($lead, $executor, $excludeUserIds): LeadRoutingExecution {
            $activeRules = LeadRoutingRule::query()
                ->where('is_active', true)
                ->with(['conditions', 'cursor'])
                ->orderBy('priority', 'asc')
                ->get();

            $matchedRule = null;

            foreach ($activeRules as $rule) {
                if ($this->matchesRule($lead, $rule)) {
                    $matchedRule = $rule;
                    break;
                }
            }

            if ($matchedRule === null) {
                $hasActiveRules = LeadRoutingRule::query()->where('is_active', true)->exists();
                if (! $hasActiveRules) {
                    $matchedRule = LeadRoutingRule::query()->firstOrCreate(
                        ['name' => 'Quy tắc Phân bổ Xoay vòng Mặc định'],
                        [
                            'strategy' => 'round_robin',
                            'priority' => 999,
                            'is_active' => true,
                            'department_id' => null,
                        ]
                    );
                }
            }

            if ($matchedRule === null) {
                return LeadRoutingExecution::query()->create([
                    'lead_id' => $lead->id,
                    'rule_id' => null,
                    'assigned_user_id' => null,
                    'status' => 'unassigned_pool',
                    'candidate_user_ids' => [],
                    'reason' => 'Không tìm thấy quy tắc phân bổ phù hợp (Kho Lead chờ)',
                    'sla_due_at' => null,
                    'executed_by' => $executor?->id,
                ]);
            }

            $candidates = $this->resolveCandidates($matchedRule, $excludeUserIds ?? []);

            if ($candidates->isEmpty()) {
                return LeadRoutingExecution::query()->create([
                    'lead_id' => $lead->id,
                    'rule_id' => $matchedRule->id,
                    'assigned_user_id' => null,
                    'status' => 'no_candidate',
                    'candidate_user_ids' => [],
                    'reason' => "Quy tắc [{$matchedRule->name}] khớp điều kiện nhưng không có nhân viên Sales đủ điều kiện.",
                    'sla_due_at' => null,
                    'executed_by' => $executor?->id,
                ]);
            }

            $selectedUser = $this->selectRoundRobinUser($matchedRule, $candidates);

            // Assign lead to selected user
            $previousOwnerId = $lead->owner_id;
            $slaDueAt = $this->slaService->calculateSlaDeadline(now());

            $lead->forceFill([
                'owner_id' => $selectedUser->id,
                'department_id' => $selectedUser->department_id,
                'sla_first_touch_due_at' => $slaDueAt,
                'sla_satisfied_at' => null,
                'is_sla_overdue' => false,
                'sla_reminder_sent_at' => null,
            ])->save();

            // Record assignment history
            LeadAssignmentHistory::query()->create([
                'lead_id' => $lead->id,
                'previous_owner_id' => $previousOwnerId,
                'new_owner_id' => $selectedUser->id,
                'assigned_by' => $executor !== null ? $executor->id : $selectedUser->id,
                'reason' => "Phân bổ tự động qua quy tắc: {$matchedRule->name} (Round-Robin)",
            ]);

            $matchedDetails = [];
            if ($matchedRule->conditions->isNotEmpty()) {
                foreach ($matchedRule->conditions as $cond) {
                    if ($cond->leadSource !== null) {
                        $matchedDetails[] = "Nguồn [{$cond->leadSource->name}]";
                    }
                    if ($cond->min_estimated_value !== null) {
                        $valStr = number_format((float) $cond->min_estimated_value, 0, ',', '.');
                        $matchedDetails[] = "GTDK >= {$valStr}đ";
                    }
                }
            }

            $candidateCount = $candidates->count();
            $matchedDetails[] = "Xoay vòng trong {$candidateCount} NV Sales";

            $reasonText = sprintf('Khớp Quy tắc [%s]: %s', $matchedRule->name, implode(' | ', $matchedDetails));

            return LeadRoutingExecution::query()->create([
                'lead_id' => $lead->id,
                'rule_id' => $matchedRule->id,
                'assigned_user_id' => $selectedUser->id,
                'status' => 'success',
                'candidate_user_ids' => $candidates->pluck('id')->all(),
                'reason' => $reasonText,
                'sla_due_at' => $slaDueAt,
                'executed_by' => $executor?->id,
            ]);
        });
    }

    private function matchesRule(Lead $lead, LeadRoutingRule $rule): bool
    {
        if ($rule->conditions->isEmpty()) {
            return true;
        }

        foreach ($rule->conditions as $condition) {
            if ($condition->lead_source_id !== null && (int) $lead->lead_source_id !== (int) $condition->lead_source_id) {
                continue;
            }

            if ($condition->min_estimated_value !== null) {
                $leadVal = (float) ($lead->estimated_value ?? 0);
                if ($leadVal < (float) $condition->min_estimated_value) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param  array<int>  $excludeUserIds
     * @return Collection<int, User>
     */
    private function resolveCandidates(LeadRoutingRule $rule, array $excludeUserIds = []): Collection
    {
        $query = User::query()
            ->where('is_active', true)
            ->whereNotNull('email_verified_at');

        if (! empty($excludeUserIds)) {
            $query->whereNotIn('id', $excludeUserIds);
        }

        if ($rule->department_id !== null) {
            $query->where('department_id', $rule->department_id);
        } else {
            // Default to Sales department
            $salesDept = Department::query()->where('code', 'SALES')->first();
            if ($salesDept !== null) {
                $query->where('department_id', $salesDept->id);
            }
        }

        if ($rule->target_role_id !== null) {
            $query->whereHas('roles', function ($q) use ($rule): void {
                $q->where('roles.id', $rule->target_role_id);
            });
        }

        return $query->orderBy('id', 'asc')->get();
    }

    /**
     * @param  Collection<int, User>  $candidates
     */
    private function selectRoundRobinUser(LeadRoutingRule $rule, Collection $candidates): User
    {
        $cursor = LeadRoutingCursor::query()->firstOrCreate(
            ['rule_id' => $rule->id],
            ['last_assigned_user_id' => null]
        );

        $candidateIds = $candidates->pluck('id')->values()->all();
        $lastUserId = $cursor->last_assigned_user_id;

        $nextIndex = 0;
        if ($lastUserId !== null) {
            $foundIndex = array_search($lastUserId, $candidateIds, true);
            if ($foundIndex !== false) {
                $nextIndex = ($foundIndex + 1) % count($candidateIds);
            }
        }

        $selectedUser = $candidates->firstWhere('id', $candidateIds[$nextIndex]);

        $cursor->update(['last_assigned_user_id' => $selectedUser->id]);

        return $selectedUser;
    }
}
