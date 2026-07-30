<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\QuoteDocument;
use App\Notifications\QuoteWorkflowNotification;
use App\Services\VietnameseMoneyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class RenderQuotePdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $documentId,
        public readonly bool $force = false,
    ) {
        $this->onQueue('default');
    }

    public function handle(VietnameseMoneyService $money): void
    {
        $document = QuoteDocument::query()
            ->with(['quote.creator', 'version'])
            ->findOrFail($this->documentId);
        $disk = Storage::disk($document->disk);

        if (! $this->force && $document->status === 'ready' && $disk->exists($document->path)) {
            return;
        }

        $snapshot = $document->version->snapshot;
        $logoDataUri = null;
        $logoPath = data_get($snapshot, 'branding.logo_path');
        if (is_string($logoPath) && $logoPath !== '' && Storage::disk('local')->exists($logoPath)) {
            $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = in_array($extension, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($logoPath));
        }
        $contents = Pdf::loadView('pdf.quote', [
            'snapshot' => $snapshot,
            'totalInWords' => $money->toWords((string) data_get($snapshot, 'quote.total_amount', '0')),
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4')->output();

        $disk->put($document->path, $contents);
        $document->update([
            'status' => 'ready',
            'size' => strlen($contents),
            'sha256' => hash('sha256', $contents),
            'failure_reason' => null,
            'generated_at' => now(),
        ]);

        $document->quote->creator?->notify(new QuoteWorkflowNotification(
            $document->quote,
            'PDF báo giá đã sẵn sàng',
            "{$document->original_name} đã được tạo và có thể tải xuống.",
            'quote_document_ready',
        ));
    }

    public function failed(Throwable $exception): void
    {
        QuoteDocument::query()->whereKey($this->documentId)->update([
            'status' => 'failed',
            'failure_reason' => mb_substr($exception->getMessage(), 0, 1000),
        ]);
    }
}
