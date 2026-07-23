<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tag;
use App\Repositories\Contracts\TagRepository;
use Illuminate\Database\Eloquent\Collection;

final class EloquentTagRepository implements TagRepository
{
    public function activeOrdered(): Collection
    {
        return Tag::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'color']);
    }
}
