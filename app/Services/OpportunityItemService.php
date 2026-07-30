<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Opportunity;
use App\Models\OpportunityItem;
use App\Models\PriceBookEntry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class OpportunityItemService
{
    /**
     * @param  array{product_name: string, sku?: string|null, unit_price: float, quantity: int, discount_percent?: float|null, notes?: string|null}  $data
     */
    public function addItem(User $actor, int $opportunityId, array $data): OpportunityItem
    {
        /** @var Opportunity $opportunity */
        $opportunity = Opportunity::query()->findOrFail($opportunityId);
        Gate::forUser($actor)->authorize('update', $opportunity);
        $this->authorizeCatalogReferences($actor, $data);

        return DB::transaction(function () use ($actor, $opportunity, $data): OpportunityItem {
            $unitPrice = (float) $data['unit_price'];
            $quantity = max(1, (int) $data['quantity']);
            $discountPercent = (float) ($data['discount_percent'] ?? 0);

            $totalPrice = round($quantity * $unitPrice * (1 - ($discountPercent / 100)), 2);

            /** @var OpportunityItem $item */
            $item = OpportunityItem::query()->create([
                'opportunity_id' => $opportunity->id,
                'product_id' => $data['product_id'] ?? null,
                'price_book_entry_id' => $data['price_book_entry_id'] ?? null,
                'product_name' => trim($data['product_name']),
                'sku' => isset($data['sku']) && trim($data['sku']) !== '' ? trim($data['sku']) : null,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'discount_percent' => $discountPercent,
                'vat_percent' => (float) ($data['vat_percent'] ?? 0),
                'total_price' => $totalPrice,
                'notes' => isset($data['notes']) && trim($data['notes']) !== '' ? trim($data['notes']) : null,
            ]);

            $this->recalculateOpportunityAmount($actor, $opportunity);

            return $item;
        });
    }

    /**
     * @param  array{product_name: string, sku?: string|null, unit_price: float, quantity: int, discount_percent?: float|null, notes?: string|null}  $data
     */
    public function updateItem(User $actor, int $itemId, array $data): OpportunityItem
    {
        /** @var OpportunityItem $item */
        $item = OpportunityItem::query()->with('opportunity')->findOrFail($itemId);
        /** @var Opportunity $opportunity */
        $opportunity = $item->opportunity;
        Gate::forUser($actor)->authorize('update', $opportunity);
        $this->authorizeCatalogReferences($actor, $data);

        return DB::transaction(function () use ($actor, $item, $opportunity, $data): OpportunityItem {
            $unitPrice = (float) $data['unit_price'];
            $quantity = max(1, (int) $data['quantity']);
            $discountPercent = (float) ($data['discount_percent'] ?? 0);

            $totalPrice = round($quantity * $unitPrice * (1 - ($discountPercent / 100)), 2);

            $item->update([
                'product_name' => trim($data['product_name']),
                'product_id' => $data['product_id'] ?? null,
                'price_book_entry_id' => $data['price_book_entry_id'] ?? null,
                'sku' => isset($data['sku']) && trim($data['sku']) !== '' ? trim($data['sku']) : null,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'discount_percent' => $discountPercent,
                'vat_percent' => (float) ($data['vat_percent'] ?? 0),
                'total_price' => $totalPrice,
                'notes' => isset($data['notes']) && trim($data['notes']) !== '' ? trim($data['notes']) : null,
            ]);

            $this->recalculateOpportunityAmount($actor, $opportunity);

            return $item;
        });
    }

    public function deleteItem(User $actor, int $itemId): void
    {
        /** @var OpportunityItem $item */
        $item = OpportunityItem::query()->with('opportunity')->findOrFail($itemId);
        /** @var Opportunity $opportunity */
        $opportunity = $item->opportunity;
        Gate::forUser($actor)->authorize('update', $opportunity);

        DB::transaction(function () use ($actor, $item, $opportunity): void {
            $item->delete();
            $this->recalculateOpportunityAmount($actor, $opportunity);
        });
    }

    private function recalculateOpportunityAmount(User $actor, Opportunity $opportunity): void
    {
        $sum = (float) OpportunityItem::query()
            ->where('opportunity_id', $opportunity->id)
            ->sum('total_price');

        $opportunity->amount = $sum;
        $opportunity->updated_by = $actor->getKey();
        $opportunity->save();
    }

    /** @param array<string, mixed> $data */
    private function authorizeCatalogReferences(User $actor, array $data): void
    {
        if (($data['product_id'] ?? null) !== null) {
            $product = Product::query()->findOrFail((int) $data['product_id']);
            Gate::forUser($actor)->authorize('view', $product);
        }

        if (($data['price_book_entry_id'] ?? null) !== null) {
            $entry = PriceBookEntry::query()->with('priceBook')->findOrFail((int) $data['price_book_entry_id']);
            Gate::forUser($actor)->authorize('view', $entry->priceBook);

            if (! $entry->priceBook->is_active || ! $entry->priceBook->usable()->whereKey($entry->price_book_id)->exists()) {
                throw ValidationException::withMessages([
                    'priceBookId' => 'Bảng giá đã hết hiệu lực hoặc không còn được áp dụng.',
                ]);
            }

            if ((int) ($data['product_id'] ?? 0) !== $entry->product_id) {
                throw ValidationException::withMessages([
                    'productId' => 'Dòng giá không thuộc sản phẩm đã chọn.',
                ]);
            }
        }
    }
}
