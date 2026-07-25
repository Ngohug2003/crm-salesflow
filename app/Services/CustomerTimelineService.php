<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CustomerTimelineItemData;
use App\Models\Activity as CrmActivity;
use App\Models\Attachment;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

final readonly class CustomerTimelineService
{
    /**
     * @return list<CustomerTimelineItemData>
     */
    public function timelineForModel(User $actor, Model $model): array
    {
        $spatieActivities = SpatieActivity::query()
            ->where('subject_type', $model->getMorphClass())
            ->where('subject_id', $model->getKey())
            ->with('causer')
            ->orderByDesc('created_at')
            ->get();

        $crmActivities = CrmActivity::query()
            ->where('subject_type', $model->getMorphClass())
            ->where('subject_id', $model->getKey())
            ->with(['user', 'creator'])
            ->orderByDesc('performed_at')
            ->get();

        $attachments = Attachment::query()
            ->where('attachable_type', $model->getMorphClass())
            ->where('attachable_id', $model->getKey())
            ->with('createdBy')
            ->orderByDesc('created_at')
            ->get();

        $tasks = Task::query()
            ->where('subject_type', $model->getMorphClass())
            ->where('subject_id', $model->getKey())
            ->with(['assignee', 'creator'])
            ->orderByDesc('created_at')
            ->get();

        /** @var list<CustomerTimelineItemData> $items */
        $items = [];

        foreach ($crmActivities as $crmAct) {
            $causer = $crmAct->user ? $crmAct->user->name : ($crmAct->creator ? $crmAct->creator->name : 'Hệ thống');
            $desc = $crmAct->description ?: '';
            if ($crmAct->duration_minutes) {
                $desc .= ($desc !== '' ? ' • ' : '')."Thời lượng: {$crmAct->duration_minutes} phút";
            }
            if ($crmAct->location) {
                $desc .= ($desc !== '' ? ' • ' : '')."Địa điểm: {$crmAct->location}";
            }

            $type = $crmAct->activity_type;

            $items[] = new CustomerTimelineItemData(
                type: 'activity',
                event: $type->value,
                title: "[{$type->label()}] {$crmAct->title}",
                description: $desc !== '' ? $desc : null,
                causer: $causer,
                timestamp: $crmAct->performed_at ?? $crmAct->created_at ?? now(),
                metadata: [
                    'activity_id' => $crmAct->id,
                    'activity_type' => $type->value,
                    'activity_type_label' => $type->label(),
                    'activity_type_icon' => $type->icon(),
                    'activity_type_color' => $type->color(),
                    'location' => $crmAct->location,
                    'duration_minutes' => $crmAct->duration_minutes,
                ],
            );
        }

        foreach ($spatieActivities as $act) {
            $causer = $act->causer instanceof User ? $act->causer->name : 'Hệ thống';
            $desc = (string) $act->description;
            $props = $act->properties ? $act->properties->toArray() : [];

            $items[] = new CustomerTimelineItemData(
                type: 'audit',
                event: (string) ($act->event ?? 'updated'),
                title: $desc !== '' ? $desc : 'Cập nhật bản ghi',
                description: isset($props['new']['duplicate_override']['reason'])
                    ? 'Lý do ghi đè trùng: '.$props['new']['duplicate_override']['reason']
                    : null,
                causer: $causer,
                timestamp: $act->created_at ?? now(),
                metadata: $props,
            );
        }

        foreach ($attachments as $att) {
            $causer = $att->createdBy ? $att->createdBy->name : 'Hệ thống';
            $items[] = new CustomerTimelineItemData(
                type: 'attachment',
                event: 'file_uploaded',
                title: "Tải lên tệp {$att->file_name}",
                description: "Dung lượng: {$att->humanSize()}",
                causer: $causer,
                timestamp: Carbon::parse((string) $att->created_at),
                metadata: [
                    'file_name' => $att->file_name,
                    'file_size' => $att->humanSize(),
                ],
            );
        }

        foreach ($tasks as $t) {
            $assignee = $t->assignee;
            $causer = $assignee !== null ? $assignee->name : $t->creator->name;
            $statusLabel = $t->status->label();
            $priorityLabel = $t->priority->label();
            $dueStr = $t->due_date ? ' • Hạn: '.$t->due_date->format('d/m/Y H:i') : '';

            $items[] = new CustomerTimelineItemData(
                type: 'task',
                event: 'task_created',
                title: "[Công việc - {$statusLabel}] {$t->title}",
                description: "Mức độ: {$priorityLabel}{$dueStr}".($t->description ? " • {$t->description}" : ''),
                causer: $causer,
                timestamp: $t->created_at ?? now(),
                metadata: [
                    'task_id' => $t->id,
                    'status' => $t->status->value,
                    'priority' => $t->priority->value,
                ],
            );
        }

        if ($model instanceof Opportunity) {
            $stageHistories = OpportunityStageHistory::query()
                ->where('opportunity_id', $model->id)
                ->with(['fromStage', 'toStage', 'user'])
                ->orderByDesc('created_at')
                ->get();

            foreach ($stageHistories as $sh) {
                $causer = $sh->user ? $sh->user->name : 'Hệ thống';
                $fromName = $sh->fromStage?->name ?: 'Khởi tạo';
                $toName = $sh->toStage?->name ?: 'N/A';
                $duration = $sh->duration_seconds !== null ? round($sh->duration_seconds / 86400, 1).' ngày' : null;
                $desc = "Từ '{$fromName}' sang '{$toName}'";
                if ($duration !== null) {
                    $desc .= " (Dừng ở stage trước: {$duration})";
                }
                if ($sh->notes) {
                    $desc .= " - Ghi chú: {$sh->notes}";
                }

                $items[] = new CustomerTimelineItemData(
                    type: 'stage_change',
                    event: 'stage_transition',
                    title: "Chuyển giai đoạn bán hàng sang {$toName}",
                    description: $desc,
                    causer: $causer,
                    timestamp: Carbon::parse((string) $sh->created_at),
                    metadata: [
                        'from_stage' => $fromName,
                        'to_stage' => $toName,
                        'notes' => $sh->notes,
                    ],
                );
            }
        }

        usort($items, static fn (CustomerTimelineItemData $a, CustomerTimelineItemData $b) => $b->timestamp->timestamp <=> $a->timestamp->timestamp);

        return $items;
    }
}
