<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\LeadSource;
use Illuminate\Database\Eloquent\Collection;

interface LeadSourceRepository
{
    /** @return Collection<int, LeadSource> */
    public function activeOrdered(): Collection;
}
