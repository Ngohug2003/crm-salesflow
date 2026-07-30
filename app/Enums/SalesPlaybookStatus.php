<?php

declare(strict_types=1);

namespace App\Enums;

enum SalesPlaybookStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bản nháp',
            self::Published => 'Đã phát hành',
            self::Archived => 'Đã lưu trữ',
        };
    }
}
