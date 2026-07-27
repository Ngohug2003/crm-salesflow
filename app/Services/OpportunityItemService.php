<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Opportunity;
use App\Models\OpportunityItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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

        return DB::transaction(function () use ($actor, $opportunity, $data): OpportunityItem {
            $unitPrice = (float) $data['unit_price'];
            $quantity = max(1, (int) $data['quantity']);
            $discountPercent = (float) ($data['discount_percent'] ?? 0);

            $totalPrice = round($quantity * $unitPrice * (1 - ($discountPercent / 100)), 2);

            /** @var OpportunityItem $item */
            $item = OpportunityItem::query()->create([
                'opportunity_id' => $opportunity->id,
                'product_name' => trim($data['product_name']),
                'sku' => isset($data['sku']) && trim($data['sku']) !== '' ? trim($data['sku']) : null,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'discount_percent' => $discountPercent,
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

        return DB::transaction(function () use ($actor, $item, $opportunity, $data): OpportunityItem {
            $unitPrice = (float) $data['unit_price'];
            $quantity = max(1, (int) $data['quantity']);
            $discountPercent = (float) ($data['discount_percent'] ?? 0);

            $totalPrice = round($quantity * $unitPrice * (1 - ($discountPercent / 100)), 2);

            $item->update([
                'product_name' => trim($data['product_name']),
                'sku' => isset($data['sku']) && trim($data['sku']) !== '' ? trim($data['sku']) : null,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'discount_percent' => $discountPercent,
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
}
