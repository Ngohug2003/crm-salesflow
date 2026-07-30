<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class ExportCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $batchId,
        public string $signedUrl,
        public int $totalRows,
        public string $fileName,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'export_completed',
            'title' => "Tệp xuất dữ liệu #{$this->batchId} đã sẵn sàng",
            'message' => "Đã xuất {$this->totalRows} dòng dữ liệu vào tệp {$this->fileName}. Đường dẫn tải về bảo mật có hiệu lực trong 24 giờ.",
            'action_url' => $this->signedUrl,
            'batch_id' => $this->batchId,
            'total_rows' => $this->totalRows,
            'file_name' => $this->fileName,
        ];
    }
}
