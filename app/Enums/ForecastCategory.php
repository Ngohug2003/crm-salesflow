<?php

declare(strict_types=1);

namespace App\Enums;

enum ForecastCategory: string
{
    case Omitted = 'omitted';
    case Pipeline = 'pipeline';
    case BestCase = 'best_case';
    case Commit = 'commit';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Omitted => 'Loại trừ',
            self::Pipeline => 'Pipeline',
            self::BestCase => 'Kịch bản tối ưu',
            self::Commit => 'Cam kết chốt',
            self::Closed => 'Đã hoàn tất',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Omitted => 'zinc',
            self::Pipeline => 'blue',
            self::BestCase => 'amber',
            self::Commit => 'indigo',
            self::Closed => 'emerald',
        };
    }
}
