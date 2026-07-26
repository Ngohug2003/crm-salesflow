<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Jobs\ProcessQueuedExportJob;
use App\Models\ExportBatch;
use App\Models\User;
use Illuminate\Support\Facades\URL;

final class ExportExecutionService
{
    /**
     * Request a new queued export job.
     *
     * @param  array<string, mixed>  $filters
     */
    public function requestExport(User $actor, string $type = 'leads', array $filters = []): ExportBatch
    {
        /** @var ExportBatch $batch */
        $batch = ExportBatch::query()->create([
            'user_id' => $actor->id,
            'type' => $type,
            'filters' => $filters,
            'status' => 'pending',
            'total_rows' => 0,
        ]);

        ProcessQueuedExportJob::dispatch($batch->id);

        return $batch;
    }

    /**
     * Generate a temporary signed download URL for an ExportBatch (valid for 24h by default).
     */
    public function getSignedDownloadUrl(ExportBatch $batch, int $expirationMinutes = 1440): string
    {
        return URL::temporarySignedRoute(
            'exports.download',
            now()->addMinutes($expirationMinutes),
            ['batch' => $batch->id],
        );
    }
}
