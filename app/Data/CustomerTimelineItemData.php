<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonInterface;

final readonly class CustomerTimelineItemData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $type, // 'audit', 'attachment'
        public string $event, // 'created', 'updated', 'deleted', 'attachment_added'
        public string $title,
        public ?string $description,
        public ?string $causer,
        public CarbonInterface $timestamp,
        public array $metadata = [],
    ) {}
}
