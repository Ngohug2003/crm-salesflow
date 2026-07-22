<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class LeadContactNormalizer
{
    public static function email(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = Str::lower(trim($email));

        return $normalized === '' ? null : $normalized;
    }

    public static function phone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/u', '', $phone) ?? '';

        if (str_starts_with($digits, '0084')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '84') && strlen($digits) >= 10) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits === '' ? null : $digits;
    }
}
