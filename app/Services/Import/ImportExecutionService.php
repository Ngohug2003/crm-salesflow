<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Jobs\ProcessImportChunkJob;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ImportExecutionService
{
    private const int CHUNK_SIZE = 100;

    /**
     * Create a new ImportBatch record and dispatch chunk jobs to Queue.
     *
     * @param  array<string, string>  $mapping
     *
     * @throws ValidationException
     */
    public function execute(
        User $actor,
        string $tempFileKey,
        string $originalFilename,
        array $mapping,
        string $duplicateStrategy = 'skip',
        string $delimiter = ',',
    ): ImportBatch {
        $relativePath = "imports/temp/{$tempFileKey}";
        if (! Storage::disk('local')->exists($relativePath)) {
            throw ValidationException::withMessages([
                'importFile' => 'Tệp tạm không tồn tại hoặc đã hết hạn. Vui lòng tải lại tệp.',
            ]);
        }

        /** @var ImportBatch $batch */
        $batch = ImportBatch::query()->create([
            'user_id' => $actor->id,
            'type' => 'leads',
            'temp_file_key' => $tempFileKey,
            'original_filename' => $originalFilename,
            'duplicate_strategy' => in_array($duplicateStrategy, ['skip', 'update', 'create_new'], true) ? $duplicateStrategy : 'skip',
            'status' => 'pending',
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'skipped_rows' => 0,
            'column_mapping' => $mapping,
        ]);

        activity('import')
            ->performedOn($batch)
            ->causedBy($actor)
            ->withProperties([
                'type' => 'leads',
                'original_filename' => $originalFilename,
                'duplicate_strategy' => $duplicateStrategy,
            ])
            ->log('Khởi tạo đợt import lead hàng loạt');

        $absolutePath = Storage::disk('local')->path($relativePath);
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            $batch->update(['status' => 'failed']);
            throw ValidationException::withMessages([
                'importFile' => 'Không thể đọc dữ liệu từ tệp tạm.',
            ]);
        }

        // Strip UTF-8 BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rawHeaders = fgetcsv($handle, 0, $delimiter);
        if ($rawHeaders === false || $rawHeaders === [null]) {
            fclose($handle);
            $batch->update(['status' => 'failed']);
            throw ValidationException::withMessages([
                'importFile' => 'Không thể đọc tiêu đề từ tệp tạm.',
            ]);
        }

        $headers = array_map(fn ($h) => trim((string) $h), $rawHeaders);

        $chunkRows = [];
        $totalRowsCount = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row === [null]) {
                continue;
            }

            $totalRowsCount++;

            $mappedRow = [];
            foreach ($headers as $colIndex => $headerName) {
                $mappedRow[$headerName] = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : '';
            }
            $chunkRows[] = $mappedRow;

            if (count($chunkRows) >= self::CHUNK_SIZE) {
                ProcessImportChunkJob::dispatch(
                    $batch->id,
                    $actor->id,
                    $chunkRows,
                    $mapping,
                    $duplicateStrategy,
                );
                $chunkRows = [];
            }
        }

        fclose($handle);

        if ($chunkRows !== []) {
            ProcessImportChunkJob::dispatch(
                $batch->id,
                $actor->id,
                $chunkRows,
                $mapping,
                $duplicateStrategy,
            );
        }

        $batch->update([
            'total_rows' => $totalRowsCount,
            'status' => $totalRowsCount === 0 ? 'completed' : 'processing',
        ]);

        return $batch;
    }

    /**
     * Download streamed error CSV file for a given ImportBatch.
     */
    public function downloadErrorCsvFile(ImportBatch $batch): StreamedResponse
    {
        $errors = $batch->error_log ?? [];

        return response()->streamDownload(function () use ($errors): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            // UTF-8 BOM for Excel
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['STT_Dòng', 'Chi_Tiết_Lỗi']);

            foreach ($errors as $errorItem) {
                fputcsv($output, [
                    $errorItem['row'] ?? 'N/A',
                    $errorItem['error'] ?? 'Không rõ nguyên nhân',
                ]);
            }

            fclose($output);
        }, "import_errors_{$batch->id}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
