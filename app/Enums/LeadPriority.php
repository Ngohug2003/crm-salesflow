<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Thấp',
            self::Medium => 'Trung bình',
            self::High => 'Cao',
            self::Urgent => 'Khẩn cấp',
        };
    }
}
