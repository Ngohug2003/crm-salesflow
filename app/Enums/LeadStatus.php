<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Unqualified = 'unqualified';
    case Converted = 'converted';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Mới',
            self::Contacted => 'Đã liên hệ',
            self::Qualified => 'Đủ điều kiện',
            self::Unqualified => 'Không đủ điều kiện',
            self::Converted => 'Đã chuyển đổi',
            self::Lost => 'Đã mất',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Contacted => 'amber',
            self::Qualified => 'emerald',
            self::Unqualified => 'zinc',
            self::Converted => 'violet',
            self::Lost => 'red',
        };
    }
}
