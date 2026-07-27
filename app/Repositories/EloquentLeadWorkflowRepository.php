<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\LeadTimelineEntry;
use App\Enums\ActivityType;
use App\Enums\LeadStatus;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\LeadAssignmentHistory;
use App\Models\LeadNote;
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

        $notes = LeadNote::query()
            ->where('lead_id', $lead->getKey())
            ->with('user:id,name')
            ->get()
            ->map(static function (LeadNote $note): LeadTimelineEntry {
                $actorName = $note->user->name;
                $occurredAt = $note->created_at ?? now();

                return new LeadTimelineEntry(
                    type: 'note',
                    title: $note->is_pinned ? 'Ghi chú đã ghim' : 'Ghi chú mới',
                    description: $note->content,
                    reason: null,
                    actorName: $actorName,
                    occurredAt: $occurredAt,
                    noteId: (int) $note->id,
                    isPinned: (bool) $note->is_pinned,
                );
            });

        $activities = Activity::query()
            ->where('subject_type', Lead::class)
            ->where('subject_id', $lead->getKey())
            ->with('user:id,name')
            ->get()
            ->map(static function (Activity $act): LeadTimelineEntry {
                $actorName = $act->user !== null ? $act->user->name : 'Hệ thống';
                $occurredAt = $act->performed_at ?? $act->created_at ?? now();
                assert($occurredAt instanceof Carbon);
                $typeLabel = $act->activity_type->label();

                return new LeadTimelineEntry(
                    type: 'activity',
                    title: "Hoạt động: {$typeLabel} - {$act->title}",
                    description: $act->description ?? 'Không có mô tả chi tiết',
                    reason: null,
                    actorName: $actorName,
                    occurredAt: $occurredAt,
                    activityType: $act->activity_type->value,
                );
            });

        $combined = $assignments
            ->concat($statuses)
            ->concat($notes)
            ->concat($activities);

        // Partition pinned notes and unpinned timeline items
        $pinned = $combined->filter(static fn (LeadTimelineEntry $e): bool => $e->isPinned)->sortByDesc('occurredAt');
        $unpinned = $combined->filter(static fn (LeadTimelineEntry $e): bool => ! $e->isPinned)->sortByDesc('occurredAt');

        return $pinned->concat($unpinned)->values();
    }
}
