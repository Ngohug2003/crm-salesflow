<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\ImportCompletedNotification;
use App\Services\LeadRoutingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProcessImportChunkJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<string, string>  $mapping
     */
    public function __construct(
        public int $importBatchId,
        public int $userId,
        public array $rows,
        public array $mapping,
        public string $duplicateStrategy,
    ) {}

    public function handle(): void
    {
        $batch = ImportBatch::query()->find($this->importBatchId);
        $user = User::query()->find($this->userId);

        if ($batch === null || $user === null) {
            return;
        }

        if ($batch->status === 'pending') {
            $batch->update(['status' => 'processing']);
        }

        $processedCount = 0;
        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($this->rows as $index => $row) {
            $processedCount++;

            try {
                $lastName = trim($row[$this->mapping['last_name'] ?? ''] ?? '');
                $firstName = trim($row[$this->mapping['first_name'] ?? ''] ?? '');
                $fullName = trim("{$lastName} {$firstName}");
                if ($fullName === '') {
                    $fullName = $firstName !== '' ? $firstName : ($lastName !== '' ? $lastName : 'Lead');
                }

                $email = isset($this->mapping['email']) ? trim($row[$this->mapping['email']] ?? '') : null;
                $phone = isset($this->mapping['phone']) ? trim($row[$this->mapping['phone']] ?? '') : null;
                $companyName = isset($this->mapping['company_name']) ? trim($row[$this->mapping['company_name']] ?? '') : null;
                $jobTitle = isset($this->mapping['title']) ? trim($row[$this->mapping['title']] ?? '') : null;
                $notes = isset($this->mapping['notes']) ? trim($row[$this->mapping['notes']] ?? '') : null;
                $status = isset($this->mapping['status']) ? trim($row[$this->mapping['status']] ?? '') : 'new';

                if ($email === '') {
                    $email = null;
                }
                if ($phone === '') {
                    $phone = null;
                }

                if ($fullName === 'Lead' && $email === null && $phone === null) {
                    $skippedCount++;

                    continue;
                }

                // Check for existing lead by email or phone
                $existingLead = null;
                if ($email !== null || $phone !== null) {
                    $existingLead = Lead::query()
                        ->where(function ($q) use ($email, $phone): void {
                            if ($email !== null) {
                                $q->where('email', $email);
                            }
                            if ($phone !== null) {
                                $q->orWhere('phone', $phone);
                            }
                        })
                        ->first();
                }

                if ($existingLead !== null) {
                    if ($this->duplicateStrategy === 'skip') {
                        $skippedCount++;

                        continue;
                    }

                    if ($this->duplicateStrategy === 'update') {
                        $updateData = array_filter([
                            'full_name' => $fullName !== 'Lead' ? $fullName : $existingLead->full_name,
                            'company_name' => $companyName ?? $existingLead->company_name,
                            'job_title' => $jobTitle ?? $existingLead->job_title,
                            'notes' => $notes !== null ? trim(($existingLead->notes ?? '')."\n[Import update]: ".$notes) : $existingLead->notes,
                        ], fn ($v) => $v !== null);

                        $existingLead->update($updateData);
                        $successCount++;

                        continue;
                    }
                }

                // Create new lead and trigger auto-routing
                $importedLead = Lead::query()->create([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone,
                    'company_name' => $companyName,
                    'job_title' => $jobTitle,
                    'notes' => $notes,
                    'status' => in_array($status, ['new', 'contacted', 'qualified', 'unqualified', 'converted'], true) ? $status : 'new',
                    'owner_id' => null,
                    'department_id' => $user->department_id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                app(LeadRoutingService::class)->routeLead($importedLead, $user);

                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[] = [
                    'row' => $index + 1,
                    'error' => $e->getMessage(),
                ];
                Log::warning("Import chunk error on batch {$batch->id}: ".$e->getMessage());
            }
        }

        // Atomic update on import_batches
        DB::table('import_batches')
            ->where('id', $this->importBatchId)
            ->update([
                'processed_rows' => DB::raw("processed_rows + {$processedCount}"),
                'successful_rows' => DB::raw("successful_rows + {$successCount}"),
                'failed_rows' => DB::raw("failed_rows + {$failedCount}"),
                'skipped_rows' => DB::raw("skipped_rows + {$skippedCount}"),
                'updated_at' => now(),
            ]);

        // Check completion status
        $freshBatch = ImportBatch::query()->find($this->importBatchId);
        if ($freshBatch !== null && $freshBatch->processed_rows >= $freshBatch->total_rows) {
            $freshBatch->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $user->notify(new ImportCompletedNotification(
                $freshBatch->id,
                'completed',
                $freshBatch->total_rows,
                $freshBatch->successful_rows,
                $freshBatch->failed_rows,
                $freshBatch->skipped_rows,
            ));
        }
    }
}
