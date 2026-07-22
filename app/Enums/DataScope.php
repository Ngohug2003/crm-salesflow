<?php

declare(strict_types=1);

namespace App\Enums;

enum DataScope: string
{
    case All = 'all';
    case Department = 'department';
    case Owned = 'owned';
    case ReadOnly = 'read-only';

    public function priority(): int
    {
        return match ($this) {
            self::All => 40,
            self::Department => 30,
            self::Owned => 20,
            self::ReadOnly => 10,
        };
    }

    public function isReadOnly(): bool
    {
        return $this === self::ReadOnly;
    }
}
