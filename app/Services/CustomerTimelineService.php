<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CustomerTimelineItemData;
use App\Models\Attachment;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

final readonly class CustomerTimelineService
{
    /**
     * @return list<CustomerTimelineItemData>
     */
    public function timelineForModel(User $actor, Model $model): array
    {
        $activities = Activity::query()
            ->where('subject_type', $model->getMorphClass())
            ->where('subject_id', $model->getKey())
            ->with('causer')
            ->orderByDesc('created_at')
            ->get();

        $attachments = Attachment::query()
            ->where('attachable_type', $model->getMorphClass())
            ->where('attachable_id', $model->getKey())
            ->with('createdBy')
            ->orderByDesc('created_at')
            ->get();

        /** @var list<CustomerTimelineItemData> $items */
        $items = [];

        foreach ($activities as $act) {
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
                event: 'attachment_added',
                title: "Tải lên tệp đính kèm: {$att->file_name}",
                description: "Dung lượng: {$att->humanSize()}",
                causer: $causer,
                timestamp: $att->created_at ?? now(),
                metadata: [
                    'attachment_id' => $att->id,
                    'file_name' => $att->file_name,
                    'file_size' => $att->humanSize(),
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
