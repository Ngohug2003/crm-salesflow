<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\ExportBatch;
use App\Models\Lead;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ProcessQueuedExportJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $exportBatchId,
    ) {}

    public function handle(DataScopeService $dataScopeService): void
    {
        $batch = ExportBatch::query()->find($this->exportBatchId);
        if ($batch === null) {
            return;
        }

        $user = User::query()->find($batch->user_id);
        if ($user === null) {
            $batch->update([
                'status' => 'failed',
                'error_message' => 'Người dùng yêu cầu không còn tồn tại.',
            ]);

            return;
        }

        $batch->update(['status' => 'processing']);

        try {
            /** @var Builder<Lead> $query */
            $query = $dataScopeService->apply(
                Lead::query(),
                $user,
                'owner_id',
                'department_id',
            );

            // Apply relations
            $query->with(['source', 'owner', 'department']);

            // Apply search or status filters if provided in batch filters
            $filters = $batch->filters ?? [];
            if (! empty($filters['search']) && is_string($filters['search'])) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search): void {
                    $like = "%{$search}%";
                    $q->whereLike('full_name', $like, caseSensitive: false)
                        ->orWhereLike('email', $like, caseSensitive: false)
                        ->orWhereLike('phone', $like, caseSensitive: false)
                        ->orWhereLike('company_name', $like, caseSensitive: false);
                });
            }

            if (! empty($filters['status']) && is_string($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $fileName = "leads_export_#{$batch->id}_".now()->format('Ymd_His').'.csv';
            $relativePath = "exports/{$user->id}/{$fileName}";

            // Ensure directory exists in local storage
            Storage::disk('local')->makeDirectory("exports/{$user->id}");
            $absolutePath = Storage::disk('local')->path($relativePath);

            $handle = fopen($absolutePath, 'w');
            if ($handle === false) {
                throw new \RuntimeException('Không thể tạo tệp lưu trữ xuất dữ liệu.');
            }

            // UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($handle, [
                'Mã Lead',
                'Họ và Tên',
                'Email',
                'Số Điện Thoại',
                'Số ĐT Phụ',
                'Công Ty',
                'Chức Danh',
                'Địa Chỉ',
                'Thành Phố',
                'Tỉnh/Thành',
                'Giá Trị Ước Tính (VND)',
                'Trạng Thái',
                'Độ Ưu Tiên',
                'Nguồn Lead',
                'Người Phụ Trách',
                'Phòng Ban',
                'Ngày Tạo',
            ]);

            $rowCount = 0;

            $query->chunk(200, function ($leads) use ($handle, &$rowCount): void {
                /** @var Lead $lead */
                foreach ($leads as $lead) {
                    $rowCount++;

                    /** @var mixed $rawStatus */
                    $rawStatus = $lead->status;
                    /** @var mixed $rawPriority */
                    $rawPriority = $lead->priority;

                    $statusLabel = $rawStatus instanceof LeadStatus
                        ? $rawStatus->label()
                        : (is_string($rawStatus) ? (LeadStatus::tryFrom($rawStatus)?->label() ?? $rawStatus) : '');

                    $priorityLabel = $rawPriority instanceof LeadPriority
                        ? $rawPriority->label()
                        : (is_string($rawPriority) ? (LeadPriority::tryFrom($rawPriority)?->label() ?? $rawPriority) : '');

                    fputcsv($handle, [
                        $lead->id,
                        $lead->full_name,
                        $lead->email ?? '',
                        $lead->phone ?? '',
                        $lead->secondary_phone ?? '',
                        $lead->company_name ?? '',
                        $lead->job_title ?? '',
                        $lead->address ?? '',
                        $lead->city ?? '',
                        $lead->province ?? '',
                        $lead->estimated_value !== null ? number_format((float) $lead->estimated_value) : '',
                        $statusLabel,
                        $priorityLabel,
                        $lead->source->name ?? '',
                        $lead->owner->name ?? 'Chưa phân công',
                        $lead->department->name ?? 'Chưa phân bổ',
                        $lead->created_at?->format('d/m/Y H:i:s') ?? '',
                    ]);
                }
            });

            fclose($handle);

            $fileSize = Storage::disk('local')->size($relativePath);

            $batch->update([
                'file_path' => $relativePath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'total_rows' => $rowCount,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error("Queued Export Error batch #{$batch->id}: ".$e->getMessage());
            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
