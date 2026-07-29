<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\QuoteTotals;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class QuotePricingService
{
    /**
     * @param  list<array{quantity: int, unit_price: int|float|string, discount_percent?: int|float|string}>  $items
     */
    public function calculate(array $items, int|float|string $discountAmount, int|float|string $taxPercent): QuoteTotals
    {
        $subtotal = BigDecimal::zero();

        foreach ($items as $item) {
            $subtotal = $subtotal->plus($this->lineTotal(
                $item['quantity'],
                $item['unit_price'],
                $item['discount_percent'] ?? 0,
            ));
        }

        $subtotal = $subtotal->toScale(2, RoundingMode::HALF_UP);
        $discount = $this->money($discountAmount);
        if ($discount->isGreaterThan($subtotal)) {
            $discount = $subtotal;
        }

        $taxable = $subtotal->minus($discount);
        $tax = $taxable
            ->multipliedBy($this->percent($taxPercent))
            ->dividedBy(100, 8, RoundingMode::HALF_UP)
            ->toScale(2, RoundingMode::HALF_UP);
        $discountPercent = $subtotal->isZero()
            ? BigDecimal::zero()
            : $discount->multipliedBy(100)->dividedBy($subtotal, 4, RoundingMode::HALF_UP);

        return new QuoteTotals(
            subtotal: (string) $subtotal,
            discountAmount: (string) $discount->toScale(2, RoundingMode::HALF_UP),
            discountPercent: (string) $discountPercent->toScale(2, RoundingMode::HALF_UP),
            taxAmount: (string) $tax,
            totalAmount: (string) $taxable->plus($tax)->toScale(2, RoundingMode::HALF_UP),
        );
    }

    public function lineTotal(
        int $quantity,
        int|float|string $unitPrice,
        int|float|string $discountPercent,
    ): string {
        $base = $this->money($unitPrice)->multipliedBy(max(1, $quantity));
        $discount = $base
            ->multipliedBy($this->percent($discountPercent))
            ->dividedBy(100, 8, RoundingMode::HALF_UP);

        return (string) $base->minus($discount)->toScale(2, RoundingMode::HALF_UP);
    }

    private function money(int|float|string $value): BigDecimal
    {
        $decimal = BigDecimal::of((string) $value);

        return $decimal->isNegative() ? BigDecimal::zero() : $decimal;
    }

    private function percent(int|float|string $value): BigDecimal
    {
        $decimal = BigDecimal::of((string) $value);

        if ($decimal->isNegative()) {
            return BigDecimal::zero();
        }

        return $decimal->isGreaterThan(100) ? BigDecimal::of(100) : $decimal;
    }
}
