<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\LeadTimelineEntry;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadAssignmentHistory;
use App\Models\LeadStatusHistory;
use App\Repositories\Contracts\LeadWorkflowRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class EloquentLeadWorkflowRepository implements LeadWorkflowRepository
{
    public function createAssignmentHistory(array $attributes): LeadAssignmentHistory
    {
        return LeadAssignmentHistory::query()->create($attributes);
    }

    public function createStatusHistory(array $attributes): LeadStatusHistory
    {
        return LeadStatusHistory::query()->create($attributes);
    }

    public function timeline(Lead $lead): Collection
    {
        $assignments = LeadAssignmentHistory::query()
            ->where('lead_id', $lead->getKey())
            ->with(['previousOwner:id,name', 'newOwner:id,name', 'changedBy:id,name'])
            ->get()
            ->map(static function (LeadAssignmentHistory $history): LeadTimelineEntry {
                $from = $history->previous_owner_id === null ? 'Chưa phân công' : $history->previousOwner->name;
                $to = $history->new_owner_id === null ? 'Chưa phân công' : $history->newOwner->name;
                $actorName = $history->changed_by === null ? 'Hệ thống' : $history->changedBy->name;
                $occurredAt = $history->created_at;
                assert($occurredAt instanceof Carbon);

                return new LeadTimelineEntry(
                    type: 'assignment',
                    title: 'Thay đổi người phụ trách',
                    description: "{$from} → {$to}",
                    reason: $history->reason,
                    actorName: $actorName,
                    occurredAt: $occurredAt,
                );
            });

        $statuses = LeadStatusHistory::query()
            ->where('lead_id', $lead->getKey())
            ->with('changedBy:id,name')
            ->get()
            ->map(static function (LeadStatusHistory $history): LeadTimelineEntry {
                $fromStatus = $history->getAttribute('from_status');
                $toStatus = $history->getAttribute('to_status');
                $from = $fromStatus instanceof LeadStatus ? $fromStatus->label() : 'Khởi tạo';
                $to = $toStatus instanceof LeadStatus ? $toStatus->label() : (string) $toStatus;
                $actorName = $history->changed_by === null ? 'Hệ thống' : $history->changedBy->name;
                $occurredAt = $history->created_at;
                assert($occurredAt instanceof Carbon);

                return new LeadTimelineEntry(
                    type: 'status',
                    title: 'Chuyển trạng thái',
                    description: "{$from} → {$to}",
                    reason: $history->reason,
                    actorName: $actorName,
                    occurredAt: $occurredAt,
                );
            });

        return $assignments
            ->concat($statuses)
            ->sortByDesc('occurredAt')
            ->values();
    }
}
