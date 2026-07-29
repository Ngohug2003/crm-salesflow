<?php

declare(strict_types=1);

namespace App\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class VietnameseMoneyService
{
    /** @var list<string> */
    private array $digits = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];

    /** @var list<string> */
    private array $units = ['', 'nghìn', 'triệu', 'tỷ', 'nghìn tỷ', 'triệu tỷ'];

    public function toWords(int|float|string $amount): string
    {
        $value = BigDecimal::of((string) $amount)->toScale(0, RoundingMode::HALF_UP)->toBigInteger();
        if ($value->isZero()) {
            return 'Không đồng';
        }

        $raw = (string) $value;
        $groups = [];
        while ($raw !== '') {
            $groups[] = (int) substr($raw, -3);
            $raw = substr($raw, 0, -3);
        }

        $parts = [];
        for ($index = count($groups) - 1; $index >= 0; $index--) {
            if ($groups[$index] === 0) {
                continue;
            }

            $parts[] = trim($this->readGroup(
                $groups[$index],
                $index < count($groups) - 1,
            ).' '.($this->units[$index] ?? ''));
        }

        return ucfirst(trim(implode(' ', $parts))).' đồng';
    }

    private function readGroup(int $number, bool $full): string
    {
        $hundreds = intdiv($number, 100);
        $tens = intdiv($number % 100, 10);
        $ones = $number % 10;
        $parts = [];

        if ($hundreds > 0 || $full) {
            $parts[] = $this->digits[$hundreds].' trăm';
        }

        if ($tens > 1) {
            $parts[] = $this->digits[$tens].' mươi';
        } elseif ($tens === 1) {
            $parts[] = 'mười';
        } elseif ($ones > 0 && ($hundreds > 0 || $full)) {
            $parts[] = 'lẻ';
        }

        if ($ones > 0) {
            $parts[] = match (true) {
                $ones === 1 && $tens > 1 => 'mốt',
                $ones === 5 && $tens > 0 => 'lăm',
                default => $this->digits[$ones],
            };
        }

        return implode(' ', $parts);
    }
}
