<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class ProductCatalogService
{
    public function __construct(private SystemAuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function save(User $actor, ?Product $product, array $data): Product
    {
        Gate::forUser($actor)->authorize($product === null ? 'create' : 'update', $product ?? Product::class);

        return DB::transaction(function () use ($actor, $product, $data): Product {
            if ($product === null && Product::query()->where('sku', $data['sku'])->exists()) {
                throw ValidationException::withMessages(['sku' => 'SKU đã tồn tại.']);
            }
            if ($product !== null && Product::query()->where('sku', $data['sku'])->whereKeyNot($product->id)->exists()) {
                throw ValidationException::withMessages(['sku' => 'SKU đã tồn tại.']);
            }
            $before = $product?->only(['sku', 'name', 'standard_price', 'vat_percent', 'is_active']) ?? [];
            $product ??= new Product(['owner_id' => $actor->id, 'department_id' => $actor->department_id, 'created_by' => $actor->id]);
            $product->fill([...$data, 'updated_by' => $actor->id])->save();
            $this->audit->record($actor, $product, $before === [] ? 'created' : 'updated', "Cập nhật sản phẩm {$product->sku}", $before, $product->only(['sku', 'name', 'standard_price', 'vat_percent', 'is_active']), ['module' => 'products']);

            return $product;
        });
    }
}
