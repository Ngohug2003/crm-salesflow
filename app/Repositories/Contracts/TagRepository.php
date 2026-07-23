<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

interface TagRepository
{
    /** @return Collection<int, Tag> */
    public function activeOrdered(): Collection;
}
