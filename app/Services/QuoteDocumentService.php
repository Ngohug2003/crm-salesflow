<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Exceptions\QuoteWorkflowException;
use App\Jobs\RenderQuotePdfJob;
use App\Models\Quote;
use App\Models\QuoteBrandingSetting;
use App\Models\QuoteDocument;
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

            if ($quote->status !== QuoteStatus::Approved) {
                throw new QuoteWorkflowException('Chỉ báo giá đã phê duyệt mới được phát hành.');
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

            $quote->update([
                'status' => QuoteStatus::Issued,
                'issued_at' => now(),
                'issued_snapshot' => $snapshot,
                'updated_by' => $actor->id,
            ]);

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

    /** @return array<string, mixed> */
    private function snapshot(Quote $quote): array
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
