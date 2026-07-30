<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Carbon;

final readonly class LeadTimelineEntry
{
    public function __construct(
        public string $type,
        public string $title,
        public string $description,
        public ?string $reason,
        public string $actorName,
        public Carbon $occurredAt,
        public ?int $noteId = null,
        public bool $isPinned = false,
        public ?string $activityType = null,
    ) {}
}
