<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Exceptions\QuoteWorkflowException;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final readonly class QuoteService
{
    public function __construct(
        private QuotePricingService $pricing,
        private SystemAuditService $audit,
    ) {}

    /**
     * @param  array{
     *     valid_until?: string|null,
     *     tax_percent?: float|int|string,
     *     discount_amount?: float|int|string,
     *     notes?: string|null,
     *     items?: list<array{product_name: string, sku?: string|null, quantity: int, unit_price: float|int|string, discount_percent?: float|int|string, notes?: string|null}>
     * }  $data
     */
    public function createFromOpportunity(User $actor, int $opportunityId, array $data = []): Quote
    {
        $opportunity = Opportunity::query()
            ->with(['company', 'contact', 'items'])
            ->findOrFail($opportunityId);

        Gate::forUser($actor)->authorize('update', $opportunity);
        Gate::forUser($actor)->authorize('create', Quote::class);

        return DB::transaction(function () use ($actor, $opportunity, $data): Quote {
            $items = $this->normalizedItems($opportunity, $data['items'] ?? []);
            $taxPercent = (string) ($data['tax_percent'] ?? '10');
            $discountAmount = (string) ($data['discount_amount'] ?? '0');
            $totals = $this->pricing->calculate($items, $discountAmount, $taxPercent);

            $quote = Quote::query()->create([
                'quote_number' => $this->generateQuoteNumber(),
                'opportunity_id' => $opportunity->id,
                'company_id' => $opportunity->company_id,
                'contact_id' => $opportunity->contact_id,
                'status' => QuoteStatus::Draft,
                'version' => 1,
                'valid_until' => ! empty($data['valid_until'])
                    ? CarbonImmutable::parse((string) $data['valid_until'])
                    : CarbonImmutable::now()->addDays(30),
                'subtotal' => $totals->subtotal,
                'tax_percent' => $taxPercent,
                'tax_amount' => $totals->taxAmount,
                'discount_amount' => $totals->discountAmount,
                'discount_percent' => $totals->discountPercent,
                'total_amount' => $totals->totalAmount,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($items as $item) {
                QuoteItem::query()->create([
                    'quote_id' => $quote->id,
                    ...$item,
                    'total_price' => $this->pricing->lineTotal(
                        $item['quantity'],
                        $item['unit_price'],
                        $item['discount_percent'],
                    ),
                ]);
            }

            $this->audit->record(
                $actor,
                $quote,
                'created',
                "Khởi tạo báo giá {$quote->quote_number}",
                null,
                [
                    'status' => $quote->status->value,
                    'version' => $quote->version,
                    'total_amount' => $quote->total_amount,
                    'discount_percent' => $quote->discount_percent,
                ],
                ['module' => 'quotes'],
            );

            return $quote->fresh(['opportunity', 'company', 'contact', 'items', 'creator']);
        });
    }

    /**
     * Kept for customer-response statuses until P10-05 owns that flow.
     */
    public function updateQuoteStatus(User $actor, Quote $quote, QuoteStatus $status): Quote
    {
        Gate::forUser($actor)->authorize('update', $quote);

        if (! in_array($status, [QuoteStatus::Sent, QuoteStatus::Accepted, QuoteStatus::Declined, QuoteStatus::Expired], true)) {
            throw new QuoteWorkflowException('Trạng thái báo giá phải được thay đổi qua đúng quy trình phê duyệt.');
        }

        $quote->update([
            'status' => $status,
            'sent_at' => $status === QuoteStatus::Sent ? now() : $quote->sent_at,
            'updated_by' => $actor->id,
        ]);

        return $quote->refresh();
    }

    public function deleteQuote(User $actor, Quote $quote): void
    {
        Gate::forUser($actor)->authorize('delete', $quote);
        $quote->delete();
    }

    /**
     * @param  list<array{product_name: string, sku?: string|null, quantity: int, unit_price: float|int|string, discount_percent?: float|int|string, notes?: string|null}>  $provided
     * @return list<array{product_name: string, sku: string|null, quantity: int, unit_price: string, discount_percent: string, notes: string|null}>
     */
    private function normalizedItems(Opportunity $opportunity, array $provided): array
    {
        if ($provided !== []) {
            return array_map(static fn (array $item): array => [
                'product_name' => trim($item['product_name']),
                'product_id' => $item['product_id'] ?? null,
                'price_book_entry_id' => $item['price_book_entry_id'] ?? null,
                'sku' => $item['sku'] ?? null,
                'quantity' => max(1, $item['quantity']),
                'unit_price' => (string) $item['unit_price'],
                'discount_percent' => (string) ($item['discount_percent'] ?? 0),
                'vat_percent' => (string) ($item['vat_percent'] ?? 0),
                'notes' => $item['notes'] ?? null,
            ], $provided);
        }

        if ($opportunity->items->isNotEmpty()) {
            return $opportunity->items->map(static fn ($item): array => [
                'product_name' => $item->product_name,
                'product_id' => $item->product_id,
                'price_book_entry_id' => $item->price_book_entry_id,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_percent' => $item->discount_percent,
                'vat_percent' => $item->vat_percent,
                'notes' => $item->notes,
            ])->values()->all();
        }

        return [[
            'product_name' => "Gói dịch vụ / Sản phẩm: {$opportunity->title}",
            'product_id' => null,
            'price_book_entry_id' => null,
            'sku' => null,
            'quantity' => 1,
            'unit_price' => (string) $opportunity->amount,
            'discount_percent' => '0',
            'vat_percent' => '0',
            'notes' => null,
        ]];
    }

    private function generateQuoteNumber(): string
    {
        do {
            $number = 'BG-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (Quote::query()->where('quote_number', $number)->exists());

        return $number;
    }
}
