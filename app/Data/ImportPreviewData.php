<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ImportPreviewData
{
    /**
     * @param  list<string>  $headers
     * @param  list<array<string, string>>  $previewRows
     */
    public function __construct(
        public string $tempFileKey,
        public string $originalFilename,
        public string $fileSizeFormatted,
        public array $headers,
        public array $previewRows,
        public int $totalRowsEstimate,
        public string $delimiter,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'temp_file_key' => $this->tempFileKey,
            'original_filename' => $this->originalFilename,
            'file_size_formatted' => $this->fileSizeFormatted,
            'headers' => $this->headers,
            'preview_rows' => $this->previewRows,
            'total_rows_estimate' => $this->totalRowsEstimate,
            'delimiter' => $this->delimiter,
        ];
    }
}
