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
    public function paginateVisible(User $actor, string $search, ?int $categoryId = null, ?int $supplierId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->visibleQuery($actor)
            ->with(['category', 'brand', 'primaryMedia'])
            ->withCount('variants')
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(static function (Builder $query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('sku', "%{$search}%");
                });
            })
            ->when($categoryId !== null, fn (Builder $query) => $query->where('product_category_id', $categoryId))
            ->when($supplierId !== null, fn (Builder $query) => $query->whereHas('variants.suppliers', fn (Builder $supplierQuery) => $supplierQuery->whereKey($supplierId)))
            ->when($status !== null && $status !== '', fn (Builder $query) => $query->where('commercial_status', $status))
            ->latest()
            ->paginate($perPage);
    }

    /** @return Collection<int, Product> */
    public function activeVisible(User $actor): Collection
    {
        return $this->visibleQuery($actor)->where('is_active', true)->orderBy('name')->get();
    }

    /** @return LengthAwarePaginator<int, Product> */
    public function paginatePublic(string $search = '', ?int $categoryId = null, int $perPage = 12): LengthAwarePaginator
    {
        return $this->publicQuery()
            ->with(['category', 'brand', 'primaryMedia'])
            ->withCount('variants')
            ->when(trim($search) !== '', static function (Builder $query) use ($search): void {
                $query->where(static function (Builder $query) use ($search): void {
                    $query->whereLike('name', '%'.trim($search).'%')
                        ->orWhereLike('sku', '%'.trim($search).'%');
                });
            })
            ->when($categoryId !== null, fn (Builder $query) => $query->where('product_category_id', $categoryId))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findPublic(int $productId): ?Product
    {
        return $this->publicQuery()
            ->with([
                'category',
                'brand',
                'media.variant',
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->where('commercial_status', '!=', 'discontinued')
                    ->orderByDesc('is_default')
                    ->orderBy('sku'),
            ])
            ->find($productId);
    }

    /** @return Builder<Product> */
    private function publicQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->where('commercial_status', '!=', 'discontinued');
    }

    /** @return Builder<Product> */
    private function visibleQuery(User $actor): Builder
    {
        return $this->dataScope->apply(Product::query(), $actor);
    }
}
