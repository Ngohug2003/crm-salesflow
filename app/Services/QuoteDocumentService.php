<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Exceptions\QuoteWorkflowException;
use App\Jobs\RenderQuotePdfJob;
use App\Models\Quote;
use App\Models\QuoteBrandingSetting;
use App\Models\QuoteDocument;
use App\Models\QuotePublicLink;
use App\Models\QuoteVersion;
use App\Models\User;
use App\Repositories\Contracts\QuoteRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class QuoteDocumentService
{
    public function __construct(
        private QuoteRepository $quotes,
        private SystemAuditService $audit,
    ) {}

    public function issue(User $actor, int $quoteId): QuoteDocument
    {
        return DB::transaction(function () use ($actor, $quoteId): QuoteDocument {
            $quote = $this->quotes->findForUpdate($quoteId);
            Gate::forUser($actor)->authorize('issue', $quote);

            if (! in_array($quote->status, [QuoteStatus::Approved, QuoteStatus::Issued, QuoteStatus::Sent, QuoteStatus::Accepted], true)) {
                throw new QuoteWorkflowException('Chỉ báo giá đã phê duyệt hoặc đã chấp thuận mới được phát hành PDF.');
            }

            $snapshot = $this->snapshot($quote);
            $version = QuoteVersion::query()->firstOrCreate(
                ['quote_id' => $quote->id, 'version' => $quote->version],
                ['snapshot' => $snapshot, 'created_by' => $actor->id],
            );
            $document = QuoteDocument::query()->firstOrCreate(
                ['quote_version_id' => $version->id],
                [
                    'quote_id' => $quote->id,
                    'disk' => (string) config('crm.quote_document_disk', 'local'),
                    'path' => "quotes/{$quote->id}/{$quote->quote_number}-v{$quote->version}.pdf",
                    'original_name' => "{$quote->quote_number}-v{$quote->version}.pdf",
                    'status' => 'processing',
                ],
            );

            $newStatus = match ($quote->status) {
                QuoteStatus::Accepted => QuoteStatus::Accepted,
                QuoteStatus::Sent => QuoteStatus::Sent,
                QuoteStatus::Issued => QuoteStatus::Issued,
                default => QuoteStatus::Issued,
            };

            $quote->update([
                'status' => $newStatus,
                'issued_at' => $quote->issued_at ?? now(),
                'issued_snapshot' => $snapshot,
                'updated_by' => $actor->id,
            ]);

            // A public token must only ever represent the issued version it captured.
            // Reissuing a newer version invalidates every older public link immediately.
            QuotePublicLink::query()
                ->where('quote_id', $quote->id)
                ->where('version_issued', '<>', $quote->version)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $this->audit->record(
                $actor,
                $quote,
                'issued',
                "Phát hành báo giá {$quote->quote_number} phiên bản {$quote->version}",
                ['status' => QuoteStatus::Approved->value],
                ['status' => QuoteStatus::Issued->value, 'document_id' => $document->id],
                ['module' => 'quotes', 'quote_version' => $quote->version],
            );

            DB::afterCommit(fn () => RenderQuotePdfJob::dispatch($document->id));

            return $document;
        });
    }

    public function markSent(User $actor, int $quoteId): Quote
    {
        return DB::transaction(function () use ($actor, $quoteId): Quote {
            $quote = $this->quotes->findForUpdate($quoteId);
            Gate::forUser($actor)->authorize('send', $quote);
            $quote->update(['status' => QuoteStatus::Sent, 'sent_at' => now(), 'updated_by' => $actor->id]);
            $this->audit->record(
                $actor,
                $quote,
                'sent',
                "Đánh dấu đã gửi báo giá {$quote->quote_number}",
                ['status' => QuoteStatus::Issued->value],
                ['status' => QuoteStatus::Sent->value],
                ['module' => 'quotes', 'quote_version' => $quote->version],
            );

            return $quote->refresh();
        });
    }

    public function regenerate(User $actor, int $quoteId): QuoteDocument
    {
        return DB::transaction(function () use ($actor, $quoteId): QuoteDocument {
            $quote = $this->quotes->findForUpdate($quoteId);
            Gate::forUser($actor)->authorize('issue', $quote);

            /** @var QuoteDocument $document */
            $document = QuoteDocument::query()
                ->where('quote_id', $quote->id)
                ->whereHas('version', fn ($query) => $query->where('version', $quote->version))
                ->lockForUpdate()
                ->firstOrFail();

            $document->update([
                'status' => 'processing',
                'failure_reason' => null,
            ]);

            $this->audit->record(
                $actor,
                $quote,
                'pdf_regenerated',
                "Yêu cầu tạo lại PDF báo giá {$quote->quote_number} phiên bản {$quote->version}",
                ['document_id' => $document->id, 'status' => 'ready'],
                ['document_id' => $document->id, 'status' => 'processing'],
                ['module' => 'quotes', 'quote_version' => $quote->version],
            );

            DB::afterCommit(fn () => RenderQuotePdfJob::dispatch($document->id, force: true));

            return $document;
        });
    }

    /** @return array<string, mixed> */
    public function snapshot(Quote $quote): array
    {
        $branding = QuoteBrandingSetting::query()->first();

        return [
            'quote' => [
                'id' => $quote->id,
                'number' => $quote->quote_number,
                'version' => $quote->version,
                'valid_until' => $quote->valid_until?->format('Y-m-d'),
                'subtotal' => $quote->subtotal,
                'discount_amount' => $quote->discount_amount,
                'discount_percent' => $quote->discount_percent,
                'tax_percent' => $quote->tax_percent,
                'tax_amount' => $quote->tax_amount,
                'total_amount' => $quote->total_amount,
                'notes' => $quote->notes,
                'issued_at' => now()->toIso8601String(),
            ],
            'opportunity' => [
                'title' => $quote->opportunity->title,
                'department' => $quote->opportunity->department?->name,
            ],
            'company' => [
                'name' => $quote->company?->name,
                'tax_code' => $quote->company?->tax_code,
                'address' => $quote->company?->address,
                'email' => $quote->company?->email,
                'phone' => $quote->company?->phone,
            ],
            'contact' => [
                'name' => $quote->contact?->full_name,
                'email' => $quote->contact?->email,
                'phone' => $quote->contact?->phone,
            ],
            'creator' => [
                'name' => $quote->creator?->name,
                'email' => $quote->creator?->email,
            ],
            'items' => $quote->items->map(static fn ($item): array => [
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_percent' => $item->discount_percent,
                'total_price' => $item->total_price,
                'notes' => $item->notes,
            ])->all(),
            'branding' => $branding?->only([
                'company_name', 'tax_code', 'address', 'hotline', 'email',
                'logo_path', 'payment_terms', 'bank_information',
            ]) ?? [
                'company_name' => 'SalesFlow CRM',
                'tax_code' => null,
                'address' => null,
                'hotline' => null,
                'email' => null,
                'logo_path' => null,
                'payment_terms' => null,
                'bank_information' => null,
            ],
        ];
    }
}
