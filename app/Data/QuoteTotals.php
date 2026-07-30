<?php

declare(strict_types=1);

namespace App\Data;

final readonly class QuoteTotals
{
    public function __construct(
        public string $subtotal,
        public string $discountAmount,
        public string $discountPercent,
        public string $taxAmount,
        public string $totalAmount,
    ) {}
}
