<?php

declare(strict_types=1);

namespace App\Services\ImportExport;

use App\Models\ExportBatch;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportHistoryService
{
    /**
     * Get paginated export batches filtered by user scope and search filters.
     *
     * @param  array{search?: string, type?: string, status?: string}  $filters
     * @return LengthAwarePaginator<int, ExportBatch>
     */
    public function getPaginatedBatches(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ExportBatch::query()->with('user')->orderByDesc('created_at');

        $superAdminRole = (string) config('crm.rbac.super_admin_role', 'super-admin');
        if (! $user->hasRole($superAdminRole) && ! $user->hasRole('admin') && ! $user->can('users.manage')) {
            $query->where('user_id', $user->getKey());
        }

        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('file_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search): void {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $type = $filters['type'] ?? 'all';
        if ($type !== 'all' && $type !== '') {
            $query->where('type', $type);
        }

        $status = $filters['status'] ?? 'all';
        if ($status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        /** @var LengthAwarePaginator<int, ExportBatch> $result */
        $result = $query->paginate($perPage);

        return $result;
    }

    /**
     * Get summary stats for export batches.
     *
     * @return array{
     *     total_batches: int,
     *     completed_batches: int,
     *     total_exported_rows: int,
     *     total_file_size_formatted: string
     * }
     */
    public function getExportSummaryStats(User $user): array
    {
        $query = ExportBatch::query();

        $superAdminRole = (string) config('crm.rbac.super_admin_role', 'super-admin');
        if (! $user->hasRole($superAdminRole) && ! $user->hasRole('admin') && ! $user->can('users.manage')) {
            $query->where('user_id', $user->getKey());
        }

        $totalBytes = (int) (clone $query)->sum('file_size');

        return [
            'total_batches' => (int) (clone $query)->count(),
            'completed_batches' => (int) (clone $query)->where('status', 'completed')->count(),
            'total_exported_rows' => (int) (clone $query)->sum('total_rows'),
            'total_file_size_formatted' => $this->formatBytes($totalBytes),
        ];
    }

    /**
     * Stream export file download response.
     */
    public function downloadExportFileStream(ExportBatch $batch): StreamedResponse
    {
        $fileName = $batch->file_name ?? ('export_data_batch_'.$batch->id.'.csv');
        $filePath = $batch->file_path;

        if ($filePath !== null && Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->download($filePath, $fileName);
        }

        // Fallback dynamic stream if file on disk is unavailable
        return response()->streamDownload(function () use ($batch): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Mã tệp', 'Loại xuất', 'Tổng số bản ghi', 'Thời gian hoàn tất']);
            fputcsv($handle, [
                $batch->id,
                strtoupper($batch->type),
                $batch->total_rows,
                $batch->completed_at ? $batch->completed_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s'),
            ]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Format byte size to human readable units.
     */
    public function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / pow(1024, $i), 2).' '.$units[$i];
    }
}
