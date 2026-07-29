<?php

declare(strict_types=1);

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quotes')
            ->select(['id', 'subtotal', 'discount_amount'])
            ->orderBy('id')
            ->chunkById(500, static function ($quotes): void {
                foreach ($quotes as $quote) {
                    $subtotal = BigDecimal::of((string) $quote->subtotal);
                    $discount = BigDecimal::of((string) $quote->discount_amount);
                    $percent = $subtotal->isZero()
                        ? BigDecimal::zero()
                        : $discount->multipliedBy(100)->dividedBy($subtotal, 2, RoundingMode::HALF_UP);

                    if ($percent->isNegative()) {
                        $percent = BigDecimal::zero();
                    } elseif ($percent->isGreaterThan(100)) {
                        $percent = BigDecimal::of(100);
                    }

                    DB::table('quotes')->where('id', $quote->id)->update([
                        'discount_percent' => (string) $percent->toScale(2, RoundingMode::HALF_UP),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Data backfill is intentionally retained on rollback.
    }
};
