<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LeadSource;
use App\Repositories\Contracts\LeadSourceRepository;
use Illuminate\Database\Eloquent\Collection;

final class EloquentLeadSourceRepository implements LeadSourceRepository
{
    public function activeOrdered(): Collection
    {
        return LeadSource::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'code', 'color']);
    }
}
