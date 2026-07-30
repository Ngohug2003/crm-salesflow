<?php

declare(strict_types=1);

namespace App\Enums;

enum PlaybookRepeatPolicy: string
{
    case Once = 'once';
    case EveryEntry = 'every_entry';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'Chỉ một lần',
            self::EveryEntry => 'Mỗi lần vào stage',
            self::Manual => 'Khởi chạy thủ công',
        };
    }
}
