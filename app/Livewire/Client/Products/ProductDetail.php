<?php

declare(strict_types=1);

namespace App\Livewire\Client\Products;

use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Repositories\EloquentProductCatalogRepository;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.client')]
final class ProductDetail extends Component
{
    public int $productId;

    public ?int $selectedMediaId = null;

    public ?int $selectedVariantId = null;

    public function mount(int $productId, EloquentProductCatalogRepository $repository): void
    {
        $this->productId = $productId;
        $product = $repository->findPublic($productId);
        abort_if($product === null, 404);
        /** @var ProductMedia|null $primaryMedia */
        $primaryMedia = $product->media->firstWhere('is_primary', true) ?? $product->media->first();
        /** @var ProductVariant|null $defaultVariant */
        $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
        $this->selectedMediaId = $primaryMedia?->id;
        $this->selectedVariantId = $defaultVariant?->id;
    }

    public function selectMedia(int $mediaId, EloquentProductCatalogRepository $repository): void
    {
        $product = $repository->findPublic($this->productId);
        if ($product === null || ! $product->media->contains('id', $mediaId)) {
            return;
        }

        $this->selectedMediaId = $mediaId;
    }

    public function selectVariant(int $variantId, EloquentProductCatalogRepository $repository): void
    {
        $product = $repository->findPublic($this->productId);
        if ($product === null || ! $product->variants->contains('id', $variantId)) {
            return;
        }

        $this->selectedVariantId = $variantId;
    }

    public function render(EloquentProductCatalogRepository $repository): View
    {
        $product = $repository->findPublic($this->productId);
        if ($product === null) {
            abort(404);
        }

        /** @var ProductMedia|null $selectedMedia */
        $selectedMedia = $product->media->firstWhere('id', $this->selectedMediaId)
            ?? $product->media->firstWhere('is_primary', true)
            ?? $product->media->first();
        /** @var ProductVariant|null $selectedVariant */
        $selectedVariant = $product->variants->firstWhere('id', $this->selectedVariantId)
            ?? $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();

        return view('livewire.client.products.product-detail', [
            'product' => $product,
            'selectedMedia' => $selectedMedia,
            'selectedVariant' => $selectedVariant,
        ]);
    }
}
