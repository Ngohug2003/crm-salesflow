<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\Opportunity;
use App\Models\OpportunityItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class QuoteService
{
    /**
     * Create a new Quote from an Opportunity.
     *
     * @param array{
     *     valid_until?: string|null,
     *     tax_percent?: float|int|string,
     *     discount_amount?: float|int|string,
     *     notes?: string|null,
     *     items?: array<int, array{product_name: string, sku?: string|null, quantity: int, unit_price: float|int|string, discount_percent?: float|int|string, notes?: string|null}>
     * } $data
     */
    public function createFromOpportunity(User $actor, int $opportunityId, array $data = []): Quote
    {
        /** @var Opportunity $opp */
        $opp = Opportunity::query()
            ->with(['company', 'contact', 'items'])
            ->findOrFail($opportunityId);

        Gate::forUser($actor)->authorize('update', $opp);

        return DB::transaction(function () use ($actor, $opp, $data): Quote {
            $quoteNumber = $this->generateQuoteNumber();

            $validUntil = ! empty($data['valid_until'])
                ? CarbonImmutable::parse($data['valid_until'])
                : CarbonImmutable::now()->addDays(30);

            $taxPercent = (float) ($data['tax_percent'] ?? 10.0); // Default 10% VAT
            $discountAmount = (float) ($data['discount_amount'] ?? 0.0);
            $notes = $data['notes'] ?? null;

            /** @var Quote $quote */
            $quote = Quote::query()->create([
                'quote_number' => $quoteNumber,
                'opportunity_id' => $opp->id,
                'company_id' => $opp->company_id,
                'contact_id' => $opp->contact_id,
                'status' => QuoteStatus::Draft,
                'valid_until' => $validUntil,
                'subtotal' => 0.0,
                'tax_percent' => $taxPercent,
                'tax_amount' => 0.0,
                'discount_amount' => $discountAmount,
                'total_amount' => 0.0,
                'notes' => $notes,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            // Add items
            $subtotal = 0.0;

            if (! empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $qty = max(1, $itemData['quantity']);
                    $unitPrice = (float) $itemData['unit_price'];
                    $discPct = (float) ($itemData['discount_percent'] ?? 0.0);
                    $lineTotal = ($qty * $unitPrice) * (1 - ($discPct / 100));

                    QuoteItem::query()->create([
                        'quote_id' => $quote->id,
                        'product_name' => $itemData['product_name'],
                        'sku' => $itemData['sku'] ?? null,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'discount_percent' => $discPct,
                        'total_price' => $lineTotal,
                        'notes' => $itemData['notes'] ?? null,
                    ]);

                    $subtotal += $lineTotal;
                }
            } elseif ($opp->items->isNotEmpty()) {
                // Copy from OpportunityItems
                foreach ($opp->items as $oppItem) {
                    /** @var OpportunityItem $oppItem */
                    $lineTotal = (float) $oppItem->total_price;

                    QuoteItem::query()->create([
                        'quote_id' => $quote->id,
                        'product_name' => $oppItem->product_name,
                        'sku' => $oppItem->sku,
                        'quantity' => $oppItem->quantity,
                        'unit_price' => (float) $oppItem->unit_price,
                        'discount_percent' => (float) $oppItem->discount_percent,
                        'total_price' => $lineTotal,
                        'notes' => $oppItem->notes,
                    ]);

                    $subtotal += $lineTotal;
                }
            } else {
                // Default item using opportunity title & amount
                $lineTotal = (float) $opp->amount;
                QuoteItem::query()->create([
                    'quote_id' => $quote->id,
                    'product_name' => "Gói dịch vụ / Sản phẩm: {$opp->title}",
                    'sku' => null,
                    'quantity' => 1,
                    'unit_price' => $lineTotal,
                    'discount_percent' => 0.0,
                    'total_price' => $lineTotal,
                    'notes' => null,
                ]);

                $subtotal += $lineTotal;
            }

            // Calculate tax and total
            $effectiveDiscount = min($subtotal, max(0.0, $discountAmount));
            $taxableSubtotal = max(0.0, $subtotal - $effectiveDiscount);
            $taxAmount = $taxableSubtotal * ($taxPercent / 100);
            $totalAmount = $taxableSubtotal + $taxAmount;

            $quote->update([
                'subtotal' => $subtotal,
                'discount_amount' => $effectiveDiscount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            return $quote->fresh(['opportunity', 'company', 'contact', 'items']);
        });
    }

    public function updateQuoteStatus(User $actor, Quote $quote, QuoteStatus $status): Quote
    {
        Gate::forUser($actor)->authorize('update', $quote);

        $quote->update([
            'status' => $status,
            'updated_by' => $actor->id,
        ]);

        return $quote;
    }

    public function deleteQuote(User $actor, Quote $quote): void
    {
        Gate::forUser($actor)->authorize('delete', $quote);

        $quote->delete();
    }

    private function generateQuoteNumber(): string
    {
        $prefix = 'BG-'.date('Ymd').'-';
        $random = strtoupper(Str::random(4));

        return $prefix.$random;
    }
}
