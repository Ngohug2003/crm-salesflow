<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\LeadTimelineEntry;
use App\Models\Lead;
use App\Models\LeadAssignmentHistory;
use App\Models\LeadStatusHistory;
use Illuminate\Support\Collection;

interface LeadWorkflowRepository
{
    /** @param array<string, mixed> $attributes */
    public function createAssignmentHistory(array $attributes): LeadAssignmentHistory;

    /** @param array<string, mixed> $attributes */
    public function createStatusHistory(array $attributes): LeadStatusHistory;

    /** @return Collection<int, LeadTimelineEntry> */
    public function timeline(Lead $lead): Collection;
}
