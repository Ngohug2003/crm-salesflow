<?php

declare(strict_types=1);

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Issued = 'issued';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Declined = 'declined';
    case Expired = 'expired';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bản nháp',
            self::PendingApproval => 'Chờ phê duyệt',
            self::Approved => 'Đã phê duyệt',
            self::Issued => 'Đã phát hành',
            self::Sent => 'Đã gửi',
            self::Accepted => 'Đã chấp nhận',
            self::Rejected => 'Đã từ chối duyệt',
            self::Declined => 'Khách hàng từ chối',
            self::Expired => 'Hết hạn',
            self::Voided => 'Đã hủy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft, self::Voided => 'zinc',
            self::PendingApproval, self::Expired => 'amber',
            self::Approved, self::Accepted => 'emerald',
            self::Issued, self::Sent => 'blue',
            self::Rejected, self::Declined => 'red',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function canDownloadDocument(): bool
    {
        return in_array($this, [self::Issued, self::Sent, self::Accepted, self::Declined, self::Expired], true);
    }
}
