<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Gate;

final class Customer360Service
{
    /**
     * Get aggregated Customer 360 data for a Company.
     *
     * @return array<string, mixed>
     */
    public function getCompany360Data(User $actor, int $companyId): array
    {
        /** @var Company $company */
        $company = Company::query()
            ->with(['owner', 'department', 'contacts', 'opportunities.stage', 'opportunities.pipeline'])
            ->findOrFail($companyId);

        Gate::forUser($actor)->authorize('view', $company);

        /** @var EloquentCollection<int, Opportunity> $opportunities */
        $opportunities = Opportunity::query()
            ->where('company_id', $companyId)
            ->with(['stage', 'pipeline', 'owner'])
            ->orderByDesc('created_at')
            ->get();

        $wonRevenue = (float) $opportunities
            ->filter(static fn (Opportunity $opp): bool => (bool) $opp->stage?->is_won)
            ->sum(static fn (Opportunity $opp): float => (float) $opp->amount);

        $openPipelineValue = (float) $opportunities
            ->filter(static fn (Opportunity $opp): bool => ! (bool) $opp->stage?->is_won && ! (bool) $opp->stage?->is_lost)
            ->sum(static fn (Opportunity $opp): float => (float) $opp->amount);

        /** @var EloquentCollection<int, Contact> $contacts */
        $contacts = Contact::query()
            ->where('company_id', $companyId)
            ->orderByDesc('is_primary')
            ->orderBy('full_name')
            ->get();

        $contactIds = $contacts->pluck('id')->all();

        /** @var EloquentCollection<int, Task> $tasks */
        $tasks = Task::query()
            ->where(static function ($q) use ($companyId, $contactIds): void {
                $q->where(static function ($sub) use ($companyId): void {
                    $sub->where('subject_type', Company::class)
                        ->where('subject_id', $companyId);
                });
                if ($contactIds !== []) {
                    $q->orWhere(static function ($sub) use ($contactIds): void {
                        $sub->where('subject_type', Contact::class)
                            ->whereIn('subject_id', $contactIds);
                    });
                }
            })
            ->with(['assignee', 'creator'])
            ->orderByDesc('due_date')
            ->get();

        $openTasksCount = $tasks
            ->filter(static fn (Task $t): bool => (string) $t->status->value !== 'completed')
            ->count();

        // 360 Timeline Aggregation
        /** @var EloquentCollection<int, Activity> $activities */
        $activities = Activity::query()
            ->where(static function ($q) use ($companyId, $contactIds): void {
                $q->where(static function ($sub) use ($companyId): void {
                    $sub->where('subject_type', Company::class)
                        ->where('subject_id', $companyId);
                });
                if ($contactIds !== []) {
                    $q->orWhere(static function ($sub) use ($contactIds): void {
                        $sub->where('subject_type', Contact::class)
                            ->whereIn('subject_id', $contactIds);
                    });
                }
            })
            ->with('user:id,name')
            ->get();

        $activityEntries = $activities->map(static function (Activity $act): array {
            $occurredAt = $act->performed_at ?? $act->created_at ?? now();
            $typeLabel = $act->activity_type->label();
            $actorName = $act->user !== null ? $act->user->name : 'Hệ thống';

            return [
                'type' => 'activity',
                'title' => "Hoạt động: {$typeLabel} - {$act->title}",
                'description' => $act->description ?? 'Ghi nhận hoạt động giao tiếp khách hàng',
                'actorName' => $actorName,
                'occurredAt' => $occurredAt,
            ];
        });

        $taskEntries = $tasks->map(static function (Task $t): array {
            $occurredAt = $t->created_at ?? now();
            $actorName = $t->assignee !== null ? $t->assignee->name : 'NVKD';

            return [
                'type' => 'task',
                'title' => "Công việc: {$t->title}",
                'description' => "Trạng thái: {$t->status->label()} | Mức ưu tiên: {$t->priority->label()}",
                'actorName' => $actorName,
                'occurredAt' => $occurredAt,
            ];
        });

        $oppEntries = $opportunities->map(static function (Opportunity $opp): array {
            $occurredAt = $opp->created_at ?? now();
            $amountFormatted = number_format((float) $opp->amount, 0, ',', '.').' ₫';
            $stageName = $opp->stage !== null ? $opp->stage->name : 'Giai đoạn';
            $actorName = $opp->owner !== null ? $opp->owner->name : 'Hệ thống';

            return [
                'type' => 'opportunity',
                'title' => "Cơ hội bán hàng: {$opp->title}",
                'description' => "Giai đoạn: {$stageName} | Giá trị: {$amountFormatted}",
                'actorName' => $actorName,
                'occurredAt' => $occurredAt,
            ];
        });

        $timeline = $activityEntries
            ->concat($taskEntries)
            ->concat($oppEntries)
            ->sortByDesc('occurredAt')
            ->values();

        return [
            'company' => $company,
            'wonRevenue' => $wonRevenue,
            'openPipelineValue' => $openPipelineValue,
            'opportunitiesCount' => $opportunities->count(),
            'contactsCount' => $contacts->count(),
            'openTasksCount' => $openTasksCount,
            'opportunities' => $opportunities,
            'contacts' => $contacts,
            'tasks' => $tasks,
            'timeline' => $timeline,
        ];
    }
}
