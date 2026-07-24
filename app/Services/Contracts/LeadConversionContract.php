<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Data\LeadConversionData;
use App\Models\Lead;
use App\Models\User;

interface LeadConversionContract
{
    /**
     * Check if a Lead is eligible for conversion by the given actor.
     *
     * @return array{eligible: bool, reason: ?string, reason_code: ?string}
     */
    public function checkEligibility(User $actor, Lead $lead): array;

    /**
     * Execute the conversion contract workflow for a Lead.
     */
    public function convert(User $actor, int $leadId, LeadConversionData $data): Lead;
}
