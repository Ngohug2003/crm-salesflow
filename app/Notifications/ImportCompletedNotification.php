<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class ImportCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $batchId,
        public string $status,
        public int $total,
        public int $success,
        public int $failed,
        public int $skipped,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $isSuccess = $this->status === 'completed';
        $title = $isSuccess ? "Import Lead #{$this->batchId} đã hoàn tất" : "Import Lead #{$this->batchId} gặp sự cố";
        $message = "Đã xử lý {$this->success}/{$this->total} dòng thành công";

        if ($this->skipped > 0) {
            $message .= ", {$this->skipped} dòng trùng lặp (bỏ qua)";
        }
        if ($this->failed > 0) {
            $message .= ", {$this->failed} dòng bị lỗi";
        }
        $message .= '.';

        return [
            'type' => 'import_completed',
            'title' => $title,
            'message' => $message,
            'action_url' => route('imports.leads'),
            'batch_id' => $this->batchId,
            'status' => $this->status,
            'total_rows' => $this->total,
            'successful_rows' => $this->success,
            'failed_rows' => $this->failed,
            'skipped_rows' => $this->skipped,
        ];
    }
}
