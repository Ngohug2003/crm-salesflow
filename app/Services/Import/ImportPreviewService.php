<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Data\ImportPreviewData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ImportPreviewService
{
    private const int MAX_FILE_SIZE_BYTES = 10_485_760; // 10 MB

    private const int PREVIEW_ROWS_LIMIT = 5;

    /**
     * Store uploaded file into private storage temp directory and return temp key.
     *
     * @throws ValidationException
     */
    public function storeTempFile(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'tsv', 'txt'], true)) {
            throw ValidationException::withMessages([
                'importFile' => 'Định dạng tệp không hợp lệ. Chỉ chấp nhận tệp CSV, TSV hoặc TXT.',
            ]);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw ValidationException::withMessages([
                'importFile' => 'Dung lượng tệp vượt quá giới hạn 10MB.',
            ]);
        }

        $hashName = hash('sha256', uniqid((string) microtime(true), true)).'.csv';
        Storage::disk('local')->putFileAs('imports/temp', $file, $hashName);

        return $hashName;
    }

    /**
     * Generate preview header and rows from a stored temp file key.
     *
     * @throws ValidationException
     */
    public function generatePreview(string $tempFileKey, ?string $originalName = null): ImportPreviewData
    {
        $relativePath = "imports/temp/{$tempFileKey}";
        if (! Storage::disk('local')->exists($relativePath)) {
            throw ValidationException::withMessages([
                'importFile' => 'Không tìm thấy tệp tạm để xem trước. Vui lòng tải lại tệp.',
            ]);
        }

        $absolutePath = Storage::disk('local')->path($relativePath);
        $fileSize = Storage::disk('local')->size($relativePath);
        $fileSizeFormatted = $this->formatFileSize($fileSize);

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'importFile' => 'Không thể đọc dữ liệu từ tệp tạm.',
            ]);
        }

        // Read sample line to auto detect delimiter
        $firstLine = fgets($handle);
        if ($firstLine === false || trim($firstLine) === '') {
            fclose($handle);
            throw ValidationException::withMessages([
                'importFile' => 'Tệp tải lên rỗng hoặc không đúng định dạng.',
            ]);
        }

        $delimiter = $this->detectDelimiter($firstLine);
        rewind($handle);

        // Remove UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rawHeaders = fgetcsv($handle, 0, $delimiter);
        if ($rawHeaders === false || $rawHeaders === [null]) {
            fclose($handle);
            throw ValidationException::withMessages([
                'importFile' => 'Không thể trích xuất tiêu đề cột từ tệp.',
            ]);
        }

        $headers = [];
        foreach ($rawHeaders as $index => $rawHeader) {
            $cleaned = trim((string) $rawHeader);
            $headers[] = $cleaned !== '' ? $cleaned : 'Cột_'.($index + 1);
        }

        $previewRows = [];
        $totalRowsCount = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row === [null]) {
                continue;
            }

            $totalRowsCount++;

            if (count($previewRows) < self::PREVIEW_ROWS_LIMIT) {
                $mappedRow = [];
                foreach ($headers as $colIndex => $headerName) {
                    $mappedRow[$headerName] = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : '';
                }
                $previewRows[] = $mappedRow;
            }
        }

        fclose($handle);

        if ($totalRowsCount === 0) {
            throw ValidationException::withMessages([
                'importFile' => 'Tệp tải lên không chứa dòng dữ liệu nào.',
            ]);
        }

        return new ImportPreviewData(
            tempFileKey: $tempFileKey,
            originalFilename: $originalName ?? $tempFileKey,
            fileSizeFormatted: $fileSizeFormatted,
            headers: $headers,
            previewRows: $previewRows,
            totalRowsEstimate: $totalRowsCount,
            delimiter: $delimiter,
        );
    }

    /**
     * Download standard sample CSV template response.
     */
    public function downloadSampleCsvTemplate(): StreamedResponse
    {
        $headers = [
            'Họ',
            'Tên',
            'Email',
            'Số điện thoại',
            'Công ty',
            'Chức danh',
            'Nguồn lead',
            'Ghi chú',
        ];

        $sampleRows = [
            ['Nguyễn', 'Văn A', 'nva@example.com', '0912345678', 'Công ty ABC', 'Giám đốc kinh doanh', 'Website', 'Khách hàng quan tâm giải pháp CRM'],
            ['Trần', 'Thị B', 'ttb@example.com', '0987654321', 'Tập đoàn XYZ', 'Trưởng phòng IT', 'Triển lãm Tech Expo', 'Cần gửi báo giá chi tiết trong tuần'],
        ];

        return response()->streamDownload(function () use ($headers, $sampleRows): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            // Output UTF-8 BOM for Excel compatibility
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);

            foreach ($sampleRows as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, 'lead_import_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [',' => 0, ';' => 0, "\t" => 0];
        foreach ($delimiters as $delimiter => &$count) {
            $count = count(explode($delimiter, $line));
        }

        arsort($delimiters);

        return (string) array_key_first($delimiters);
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 2).' MB';
        }

        return number_format($bytes / 1024, 1).' KB';
    }
}
