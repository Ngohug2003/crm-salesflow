<?php

declare(strict_types=1);

namespace App\Data;

final readonly class LeadConversionData
{
    public function __construct(
        public bool $createCompany = true,
        public ?int $companyId = null,
        public ?string $companyName = null,
        public bool $createContact = true,
        public ?int $contactId = null,
        public bool $createOpportunity = true,
        public ?string $opportunityName = null,
        public ?int $pipelineId = null,
        public ?int $stageId = null,
        public ?float $estimatedValue = null,
        public ?string $notes = null,
    ) {}
}
