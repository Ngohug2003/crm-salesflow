<?php

declare(strict_types=1);

namespace App\Services\ImportExport;

use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ImportHistoryService
{
    /**
     * Get paginated import batches filtered by user scope and search filters.
     *
     * @param  array{search?: string, type?: string, status?: string}  $filters
     * @return LengthAwarePaginator<int, ImportBatch>
     */
    public function getPaginatedBatches(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ImportBatch::query()->with('user')->orderByDesc('created_at');

        if (! $this->canInspectAllBatches($user)) {
            $query->where('user_id', $user->getKey());
        }

        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('original_filename', 'like', "%{$search}%")
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

        /** @var LengthAwarePaginator<int, ImportBatch> $result */
        $result = $query->paginate($perPage);

        return $result;
    }

    public function findVisibleBatch(User $user, int $batchId): ?ImportBatch
    {
        $query = ImportBatch::query()->whereKey($batchId);

        if (! $this->canInspectAllBatches($user)) {
            $query->where('user_id', $user->getKey());
        }

        return $query->first();
    }

    /**
     * Get summary stats for import batches.
     *
     * @return array{
     *     total_batches: int,
     *     completed_batches: int,
     *     total_successful_rows: int,
     *     total_failed_rows: int
     * }
     */
    public function getImportSummaryStats(User $user): array
    {
        $query = ImportBatch::query();

        if (! $this->canInspectAllBatches($user)) {
            $query->where('user_id', $user->getKey());
        }

        return [
            'total_batches' => (int) (clone $query)->count(),
            'completed_batches' => (int) (clone $query)->where('status', 'completed')->count(),
            'total_successful_rows' => (int) (clone $query)->sum('successful_rows'),
            'total_failed_rows' => (int) (clone $query)->sum('failed_rows'),
        ];
    }

    /**
     * Stream CSV download response for a batch's error log.
     */
    public function downloadErrorLogStream(ImportBatch $batch): StreamedResponse
    {
        $filename = 'import_errors_batch_'.$batch->id.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($batch): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($handle, ['Dòng số', 'Trường dữ liệu', 'Nguyên nhân lỗi', 'Dữ liệu thô']);

            $errorLog = $batch->error_log ?? [];
            foreach ($errorLog as $index => $item) {
                $rowNum = $item['row'] ?? ($index + 1);
                $field = $item['field'] ?? 'N/A';
                $reason = $item['message'] ?? ($item['error'] ?? 'Lỗi không xác định');
                $rawData = isset($item['data']) && is_array($item['data'])
                    ? json_encode($item['data'], JSON_UNESCAPED_UNICODE)
                    : (string) ($item['data'] ?? '');

                fputcsv($handle, [$rowNum, $field, $reason, $rawData]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function canInspectAllBatches(User $user): bool
    {
        $superAdminRole = (string) config('crm.rbac.super_admin_role', 'super-admin');

        return $user->hasRole($superAdminRole)
            || $user->hasRole('admin')
            || $user->can('users.update');
    }
}
