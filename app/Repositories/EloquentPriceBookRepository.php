<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PriceBook;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class EloquentPriceBookRepository
{
    public function __construct(private DataScopeService $dataScope) {}

    /** @return Collection<int, PriceBook> */
    public function allVisible(User $actor): Collection
    {
        return $this->visibleQuery($actor)->withCount('entries')->latest()->get();
    }

    /** @return Collection<int, PriceBook> */
    public function usableVisible(User $actor): Collection
    {
        return $this->visibleQuery($actor)->usable()->orderBy('name')->get();
    }

    /** @return Builder<PriceBook> */
    private function visibleQuery(User $actor): Builder
    {
        return $this->dataScope->apply(PriceBook::query(), $actor);
    }
}
