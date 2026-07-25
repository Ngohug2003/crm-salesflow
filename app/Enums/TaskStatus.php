<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'Cần làm',
            self::InProgress => 'Đang làm',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Todo => 'zinc',
            self::InProgress => 'blue',
            self::Completed => 'emerald',
            self::Cancelled => 'rose',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
