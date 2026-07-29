<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadSlaNotification;
use App\Services\LeadRoutingService;
use App\Services\LeadSlaService;
use Illuminate\Console\Command;

final class CheckLeadSlaDeadlines extends Command
{
    protected $signature = 'salesflow:check-lead-sla';

    protected $description = 'Kiểm tra SLA phản hồi đầu tiên của Lead, gửi nhắc nhở và tự động thu hồi/reassign nếu quá hạn';

    public function handle(LeadSlaService $slaService, LeadRoutingService $routingService): int
    {
        $this->info('Đang kiểm tra SLA phản hồi đầu tiên cho các Lead...');

        $activeLeads = Lead::query()
            ->whereNotNull('owner_id')
            ->whereNotNull('sla_first_touch_due_at')
            ->whereNull('sla_satisfied_at')
            ->get();

        $overdueCount = 0;
        $reminderCount = 0;

        foreach ($activeLeads as $lead) {
            // Check if SLA satisfied in the meantime
            if ($slaService->checkAndSatisfySla($lead)) {
                continue;
            }

            $now = now('Asia/Ho_Chi_Minh');
            $dueAt = $lead->sla_first_touch_due_at;

            // Overdue SLA check
            if ($dueAt !== null && $now->gte($dueAt)) {
                $overdueCount++;
                $oldOwnerId = $lead->owner_id;

                $lead->forceFill(['is_sla_overdue' => true])->save();

                // Reassign lead excluding the owner who missed SLA
                $execution = $routingService->routeLead($lead, null, [$oldOwnerId]);

                if ($oldOwnerId !== null) {
                    $oldOwner = User::query()->find($oldOwnerId);
                    $oldOwner?->notify(new LeadSlaNotification(
                        $lead,
                        "Thu hồi Lead do quá hạn SLA: {$lead->full_name}",
                        "Lead [{$lead->full_name}] đã bị thu hồi và gán lại do không có tương tác trong hạn SLA.",
                        'sla_overdue'
                    ));
                }

                if ($execution->assigned_user_id !== null) {
                    $newOwner = User::query()->find($execution->assigned_user_id);
                    $newOwner?->notify(new LeadSlaNotification(
                        $lead,
                        "Nhận Lead mới từ thu hồi SLA: {$lead->full_name}",
                        "Bạn được tự động gán Lead [{$lead->full_name}] do chủ cũ quá hạn SLA tiếp cận.",
                        'lead_assigned'
                    ));
                }
            } elseif ($dueAt !== null && $lead->sla_reminder_sent_at === null && $now->diffInMinutes($dueAt, false) <= 30) {
                // SLA Reminder check (within 30 minutes of deadline)
                $reminderCount++;
                $lead->forceFill(['sla_reminder_sent_at' => $now])->save();

                if ($lead->owner_id !== null) {
                    $owner = User::query()->find($lead->owner_id);
                    $owner?->notify(new LeadSlaNotification(
                        $lead,
                        "Sắp hết hạn SLA phản hồi Lead: {$lead->full_name}",
                        "Lead [{$lead->full_name}] chỉ còn dưới 30 phút để thực hiện cuộc gọi/họp/note đầu tiên.",
                        'sla_warning'
                    ));
                }
            }
        }

        $this->info("Đã xử lý: {$overdueCount} Lead quá hạn SLA (đã thu hồi/gán lại), {$reminderCount} thông báo nhắc nhở.");

        return self::SUCCESS;
    }
}
