<?php

declare(strict_types=1);

namespace App\Enums;

enum QuoteApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ phê duyệt',
            self::Approved => 'Đã phê duyệt',
            self::Rejected => 'Đã từ chối',
            self::Cancelled => 'Đã hủy',
        };
    }
}
