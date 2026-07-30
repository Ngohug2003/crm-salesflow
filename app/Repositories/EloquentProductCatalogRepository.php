<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class EloquentProductCatalogRepository
{
    public function __construct(private DataScopeService $dataScope) {}

    /** @return LengthAwarePaginator<int, Product> */
    public function paginateVisible(User $actor, string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->visibleQuery($actor)
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(static function (Builder $query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('sku', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /** @return Collection<int, Product> */
    public function activeVisible(User $actor): Collection
    {
        return $this->visibleQuery($actor)->where('is_active', true)->orderBy('name')->get();
    }

    /** @return Builder<Product> */
    private function visibleQuery(User $actor): Builder
    {
        return $this->dataScope->apply(Product::query(), $actor);
    }
}
