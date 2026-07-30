<?php

declare(strict_types=1);

namespace App\Enums;

enum SalesPlaybookStepType: string
{
    case Guidance = 'guidance';
    case Question = 'question';
    case RequiredField = 'required_field';
    case Checklist = 'checklist';
    case Task = 'task';
    case Reminder = 'reminder';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Guidance => 'Mục tiêu/Hướng dẫn',
            self::Question => 'Câu hỏi qualification',
            self::RequiredField => 'Trường dữ liệu bắt buộc',
            self::Checklist => 'Checklist',
            self::Task => 'Tạo công việc',
            self::Reminder => 'Tạo nhắc nhở',
            self::Document => 'Tài liệu gợi ý',
        };
    }
}
