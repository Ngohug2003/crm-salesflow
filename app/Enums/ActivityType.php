<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityType: string
{
    case Call = 'call';
    case Meeting = 'meeting';
    case Email = 'email';
    case Note = 'note';
    case Task = 'task';
    case Demo = 'demo';
    case FollowUp = 'follow_up';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Cuộc gọi',
            self::Meeting => 'Cuộc họp',
            self::Email => 'Email',
            self::Note => 'Ghi chú',
            self::Task => 'Công việc',
            self::Demo => 'Demo sản phẩm',
            self::FollowUp => 'Theo dõi (Follow-up)',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Call => 'phone',
            self::Meeting => 'user-group',
            self::Email => 'envelope',
            self::Note => 'document-text',
            self::Task => 'clipboard-document-check',
            self::Demo => 'presentation-chart-bar',
            self::FollowUp => 'arrow-path',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Call => 'sky',
            self::Meeting => 'indigo',
            self::Email => 'amber',
            self::Note => 'zinc',
            self::Task => 'emerald',
            self::Demo => 'purple',
            self::FollowUp => 'blue',
        };
    }
}
