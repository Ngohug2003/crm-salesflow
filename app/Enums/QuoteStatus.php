<?php

declare(strict_types=1);

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bản nháp',
            self::Sent => 'Đã gửi',
            self::Accepted => 'Đã chấp nhận',
            self::Rejected => 'Đã từ chối',
            self::Expired => 'Hết hạn',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Sent => 'blue',
            self::Accepted => 'emerald',
            self::Rejected => 'red',
            self::Expired => 'amber',
        };
    }
}
