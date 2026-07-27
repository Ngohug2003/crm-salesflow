<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Data\ImportMappingSchema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ImportValidationService
{
    private const int SAMPLE_ROWS_TO_CHECK = 50;

    /**
     * Auto map CSV header names to CRM field keys.
     *
     * @param  list<string>  $headers
     * @return array<string, string> Keyed by crm_field => header_name
     */
    public function autoMapHeaders(array $headers): array
    {
        $schema = ImportMappingSchema::leadFields();
        $mapping = [];
        $usedHeaders = [];

        foreach ($schema as $crmField => $config) {
            foreach ($headers as $header) {
                if (in_array($header, $usedHeaders, true)) {
                    continue;
                }

                $normalizedHeader = Str::ascii(mb_strtolower(trim($header)));

                foreach ($config['keywords'] as $keyword) {
                    $normalizedKeyword = Str::ascii(mb_strtolower($keyword));

                    if ($normalizedHeader === $normalizedKeyword || Str::contains($normalizedHeader, $normalizedKeyword)) {
                        $mapping[$crmField] = $header;
                        $usedHeaders[] = $header;
                        break 2;
                    }
                }
            }
        }

        return $mapping;
    }

    /**
     * Ensure mapping configuration contains at least one primary identifier.
     *
     * @param  array<string, string>  $mapping
     *
     * @throws ValidationException
     */
    public function validateMappingSchema(array $mapping): void
    {
        $hasIdentifier = false;
        $primaryFields = ['first_name', 'last_name', 'email', 'phone'];

        foreach ($primaryFields as $field) {
            if (isset($mapping[$field]) && trim($mapping[$field]) !== '') {
                $hasIdentifier = true;
                break;
            }
        }

        if (! $hasIdentifier) {
            throw ValidationException::withMessages([
                'mapping' => 'Vui lòng ghép nối ít nhất 1 trường định danh chính (Họ, Tên, Email hoặc Số điện thoại).',
            ]);
        }
    }

    /**
     * Dry-run validation on sample rows from temp file.
     *
     * @param  array<string, string>  $mapping  crmField => headerName
     * @return array{total_checked: int, valid_count: int, warning_count: int, row_errors: list<array{row: int, message: string}>}
     *
     * @throws ValidationException
     */
    public function validateMappedData(string $tempFileKey, array $mapping, string $delimiter = ','): array
    {
        $this->validateMappingSchema($mapping);

        $relativePath = "imports/temp/{$tempFileKey}";
        if (! Storage::disk('local')->exists($relativePath)) {
            throw ValidationException::withMessages([
                'importFile' => 'Không tìm thấy tệp tạm để kiểm tra dữ liệu.',
            ]);
        }

        $absolutePath = Storage::disk('local')->path($relativePath);
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'importFile' => 'Không thể đọc tệp tạm.',
            ]);
        }

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rawHeaders = fgetcsv($handle, 0, $delimiter);
        if ($rawHeaders === false || $rawHeaders === [null]) {
            fclose($handle);
            throw ValidationException::withMessages([
                'importFile' => 'Không thể đọc tiêu đề từ tệp tạm.',
            ]);
        }

        $headers = array_map(fn ($h) => trim((string) $h), $rawHeaders);
        $headerIndexMap = array_flip($headers);

        $totalChecked = 0;
        $validCount = 0;
        $rowErrors = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row === [null]) {
                continue;
            }

            if ($totalChecked >= self::SAMPLE_ROWS_TO_CHECK) {
                break;
            }

            $totalChecked++;

            $rowHasError = false;
            $getValue = function (string $crmField) use ($mapping, $headerIndexMap, $row): ?string {
                $headerName = $mapping[$crmField] ?? null;
                if ($headerName === null || ! isset($headerIndexMap[$headerName])) {
                    return null;
                }
                $index = $headerIndexMap[$headerName];

                return isset($row[$index]) ? trim((string) $row[$index]) : null;
            };

            $lastName = $getValue('last_name');
            $firstName = $getValue('first_name');
            $email = $getValue('email');
            $phone = $getValue('phone');

            if (($lastName === null || $lastName === '') && ($firstName === null || $firstName === '') && ($email === null || $email === '') && ($phone === null || $phone === '')) {
                $rowErrors[] = [
                    'row' => $totalChecked + 1,
                    'message' => 'Dòng trống hoặc thiếu thông tin định danh tối thiểu (Họ, Tên, Email hoặc SĐT).',
                ];
                $rowHasError = true;
            }

            if ($email !== null && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $rowErrors[] = [
                    'row' => $totalChecked + 1,
                    'message' => "Email không đúng định dạng: '{$email}'.",
                ];
                $rowHasError = true;
            }

            if ($phone !== null && $phone !== '' && preg_match('/^[0-9\+\-\.\s\(\)]{7,20}$/', $phone) !== 1) {
                $rowErrors[] = [
                    'row' => $totalChecked + 1,
                    'message' => "Số điện thoại chứa ký tự không hợp lệ: '{$phone}'.",
                ];
                $rowHasError = true;
            }

            if (! $rowHasError) {
                $validCount++;
            }
        }

        fclose($handle);

        return [
            'total_checked' => $totalChecked,
            'valid_count' => $validCount,
            'warning_count' => count($rowErrors),
            'row_errors' => $rowErrors,
        ];
    }
}
