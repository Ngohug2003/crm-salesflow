<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductMedia;
use App\Models\ProductSupplier;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class FurnitureCatalogService
{
    public function __construct(
        private SystemAuditService $audit,
        private DataScopeService $dataScope,
    ) {}

    /** @param array<string, mixed> $data */
    public function saveCategory(User $actor, ?ProductCategory $category, array $data): ProductCategory
    {
        abort_unless($this->dataScope->canWrite($actor), 403);
        Gate::forUser($actor)->authorize($category === null ? 'products.create' : 'products.update');

        return DB::transaction(function () use ($actor, $category, $data): ProductCategory {
            $codeExists = ProductCategory::query()
                ->where('code', $data['code'])
                ->when($category !== null, fn ($query) => $query->whereKeyNot($category->id))
                ->exists();
            if ($codeExists) {
                throw ValidationException::withMessages(['categoryCode' => 'Mã danh mục đã tồn tại.']);
            }

            $before = $category?->only(['name', 'code', 'parent_id', 'sort_order', 'is_active']) ?? [];
            $category ??= new ProductCategory;
            $category->fill($data)->save();
            $this->audit->record($actor, $category, $before === [] ? 'created' : 'updated', "Cập nhật danh mục {$category->name}", $before, $category->only(['name', 'code', 'parent_id', 'sort_order', 'is_active']), ['module' => 'furniture-catalog']);

            return $category;
        });
    }

    /** @param array<string, mixed> $data */
    public function saveSupplier(User $actor, ?ProductSupplier $supplier, array $data): ProductSupplier
    {
        abort_unless($this->dataScope->canWrite($actor), 403);
        Gate::forUser($actor)->authorize($supplier === null ? 'products.create' : 'products.update');

        return DB::transaction(function () use ($actor, $supplier, $data): ProductSupplier {
            $codeExists = ProductSupplier::query()
                ->withTrashed()
                ->where('code', $data['code'])
                ->when($supplier !== null, fn ($query) => $query->whereKeyNot($supplier->id))
                ->exists();
            if ($codeExists) {
                throw ValidationException::withMessages(['supplierCode' => 'Mã nhà cung cấp đã tồn tại.']);
            }

            $before = $supplier?->only(['code', 'name', 'tax_code', 'contact_name', 'email', 'phone', 'is_active']) ?? [];
            $supplier ??= new ProductSupplier;
            $supplier->fill($data)->save();
            $this->audit->record($actor, $supplier, $before === [] ? 'created' : 'updated', "Cập nhật nhà cung cấp {$supplier->name}", $before, $supplier->only(['code', 'name', 'tax_code', 'contact_name', 'email', 'phone', 'is_active']), ['module' => 'furniture-catalog']);

            return $supplier;
        });
    }

    /** @param array<string, mixed> $data */
    public function saveVariant(User $actor, Product $product, ?ProductVariant $variant, array $data): ProductVariant
    {
        Gate::forUser($actor)->authorize('update', $product);
        if ($variant !== null && $variant->product_id !== $product->id) {
            abort(404);
        }

        return DB::transaction(function () use ($actor, $product, $variant, $data): ProductVariant {
            $skuExists = ProductVariant::query()
                ->withTrashed()
                ->where('sku', $data['sku'])
                ->when($variant !== null, fn ($query) => $query->whereKeyNot($variant->id))
                ->exists();
            if ($skuExists) {
                throw ValidationException::withMessages(['variantSku' => 'SKU biến thể đã tồn tại.']);
            }

            if ((bool) $data['is_default']) {
                ProductVariant::query()->where('product_id', $product->id)->update(['is_default' => false]);
            }

            $before = $variant?->only(['sku', 'name', 'length_mm', 'width_mm', 'height_mm', 'material', 'color', 'finish', 'standard_price', 'vat_percent', 'warranty_months', 'lead_time_days', 'commercial_status', 'is_default', 'is_active']) ?? [];
            $variant ??= new ProductVariant(['product_id' => $product->id]);
            $variant->fill($data)->save();
            $this->audit->record($actor, $variant, $before === [] ? 'created' : 'updated', "Cập nhật biến thể {$variant->sku}", $before, $variant->only(['sku', 'name', 'length_mm', 'width_mm', 'height_mm', 'material', 'color', 'finish', 'standard_price', 'vat_percent', 'warranty_months', 'lead_time_days', 'commercial_status', 'is_default', 'is_active']), ['module' => 'furniture-catalog', 'product_id' => $product->id]);

            return $variant;
        });
    }

    /** @param array<string, mixed> $data */
    public function saveSupplierOffer(User $actor, Product $product, ProductVariant $variant, ProductSupplier $supplier, array $data): void
    {
        Gate::forUser($actor)->authorize('update', $product);
        abort_unless($variant->product_id === $product->id, 404);

        DB::transaction(function () use ($actor, $variant, $supplier, $data): void {
            if ((bool) $data['is_preferred']) {
                DB::table('product_supplier_variant')
                    ->where('product_variant_id', $variant->id)
                    ->update(['is_preferred' => false, 'updated_at' => now()]);
            }

            $before = DB::table('product_supplier_variant')
                ->where('product_variant_id', $variant->id)
                ->where('product_supplier_id', $supplier->id)
                ->first();

            DB::table('product_supplier_variant')->updateOrInsert(
                ['product_variant_id' => $variant->id, 'product_supplier_id' => $supplier->id],
                [...$data, 'updated_at' => now(), 'created_at' => $before === null ? now() : $before->created_at],
            );

            $this->audit->record($actor, $variant, 'supplier_offer_updated', "Cập nhật nguồn cung cho {$variant->sku}", $before === null ? [] : (array) $before, ['supplier_id' => $supplier->id, ...$data], ['module' => 'furniture-catalog']);
        });
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return Collection<int, ProductMedia>
     */
    public function uploadMedia(User $actor, Product $product, array $files, ?ProductVariant $variant = null, bool $makeFirstPrimary = false): Collection
    {
        Gate::forUser($actor)->authorize('update', $product);
        if ($variant !== null && $variant->product_id !== $product->id) {
            abort(404);
        }

        $disk = (string) config('filesystems.default', 'local');
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($actor, $product, $variant, $files, $makeFirstPrimary, $disk, &$storedPaths): Collection {
                $nextOrder = ((int) ProductMedia::query()->where('product_id', $product->id)->max('sort_order')) + 1;
                $hasPrimary = $product->media()->where('is_primary', true)->exists();
                $created = collect();

                foreach ($files as $index => $file) {
                    $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
                    $fileName = (string) str()->uuid().'.'.$extension;
                    $path = $file->storeAs("products/{$product->id}", $fileName, $disk);
                    if (! is_string($path) || $path === '') {
                        throw new \RuntimeException('Không thể lưu ảnh sản phẩm vào kho tệp.');
                    }
                    $storedPaths[] = $path;

                    $isPrimary = $index === 0 && ($makeFirstPrimary || ! $hasPrimary);
                    if ($isPrimary) {
                        ProductMedia::query()->where('product_id', $product->id)->update(['is_primary' => false]);
                        $hasPrimary = true;
                    }

                    $created->push(ProductMedia::query()->create([
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'disk' => $disk,
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
                        'size' => (int) ($file->getSize() ?: 0),
                        'is_primary' => $isPrimary,
                        'sort_order' => $nextOrder + $index,
                        'created_by' => $actor->id,
                    ]));
                }

                $this->audit->record(
                    $actor,
                    $product,
                    'product_media_uploaded',
                    "Tải lên {$created->count()} ảnh cho sản phẩm {$product->sku}",
                    null,
                    ['media_ids' => $created->pluck('id')->all(), 'variant_id' => $variant?->id],
                    ['module' => 'furniture-catalog'],
                );

                return $created;
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk($disk)->delete($storedPaths);
            }

            throw $exception;
        }
    }

    public function setPrimaryMedia(User $actor, Product $product, ProductMedia $media): void
    {
        Gate::forUser($actor)->authorize('update', $product);
        abort_unless($media->product_id === $product->id, 404);

        DB::transaction(function () use ($actor, $product, $media): void {
            ProductMedia::query()->where('product_id', $product->id)->update(['is_primary' => false]);
            $media->update(['is_primary' => true]);
            $this->audit->record($actor, $product, 'product_media_primary_changed', "Đổi ảnh chính sản phẩm {$product->sku}", null, ['media_id' => $media->id], ['module' => 'furniture-catalog']);
        });
    }

    public function moveMedia(User $actor, Product $product, ProductMedia $media, string $direction): void
    {
        Gate::forUser($actor)->authorize('update', $product);
        abort_unless($media->product_id === $product->id, 404);
        abort_unless(in_array($direction, ['up', 'down'], true), 422);

        $comparison = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'desc' : 'asc';
        $neighbor = ProductMedia::query()
            ->where('product_id', $product->id)
            ->where('sort_order', $comparison, $media->sort_order)
            ->orderBy('sort_order', $order)
            ->first();
        if ($neighbor === null) {
            return;
        }

        DB::transaction(function () use ($media, $neighbor): void {
            $currentOrder = $media->sort_order;
            $media->update(['sort_order' => $neighbor->sort_order]);
            $neighbor->update(['sort_order' => $currentOrder]);
        });
    }

    public function deleteMedia(User $actor, Product $product, ProductMedia $media): void
    {
        Gate::forUser($actor)->authorize('update', $product);
        abort_unless($media->product_id === $product->id, 404);

        [$disk, $path] = DB::transaction(function () use ($actor, $product, $media): array {
            $wasPrimary = $media->is_primary;
            $snapshot = $media->only(['id', 'original_name', 'product_variant_id', 'is_primary']);
            $disk = $media->disk;
            $path = $media->path;
            $media->delete();

            if ($wasPrimary) {
                $product->media()->first()?->update(['is_primary' => true]);
            }

            $this->audit->record($actor, $product, 'product_media_deleted', "Xóa ảnh sản phẩm {$product->sku}", $snapshot, null, ['module' => 'furniture-catalog']);

            return [$disk, $path];
        });

        Storage::disk($disk)->delete($path);
    }
}
