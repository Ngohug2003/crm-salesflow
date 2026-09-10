<?php

declare(strict_types=1);

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\ProductCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class ProductEditor extends Component
{
    public ?int $productId = null;

    public string $sku = '';

    public string $name = '';

    public string $model = '';

    public string $categoryId = '';

    public string $brandId = '';

    public string $description = '';

    public string $usageArea = '';

    public string $unit = 'Cái';

    public string $standardPrice = '0';

    public string $vatPercent = '10';

    public string $warrantyMonths = '12';

    public string $commercialStatus = 'active';

    public bool $isActive = true;

    public function mount(?int $productId = null): void
    {
        $this->productId = $productId;
        if ($productId === null) {
            $this->authorize('create', Product::class);

            return;
        }

        $product = Product::query()->findOrFail($productId);
        $this->authorize('update', $product);
        $this->sku = $product->sku;
        $this->name = $product->name;
        $this->model = (string) $product->model;
        $this->categoryId = (string) $product->product_category_id;
        $this->brandId = (string) $product->product_brand_id;
        $this->description = (string) $product->description;
        $specifications = $product->getAttribute('specifications');
        $this->usageArea = is_array($specifications) ? (string) ($specifications['usage_area'] ?? '') : '';
        $this->unit = $product->unit;
        $this->standardPrice = (string) $product->standard_price;
        $this->vatPercent = (string) $product->vat_percent;
        $this->warrantyMonths = (string) $product->warranty_months;
        $this->commercialStatus = $product->commercial_status;
        $this->isActive = $product->is_active;
    }

    public function save(ProductCatalogService $service): mixed
    {
        $this->validate([
            'sku' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:160'],
            'categoryId' => ['required', 'integer', 'exists:product_categories,id'],
            'brandId' => ['nullable', 'integer', 'exists:product_brands,id'],
            'description' => ['nullable', 'string', 'max:3000'],
            'usageArea' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'standardPrice' => ['required', 'numeric', 'min:0'],
            'vatPercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'warrantyMonths' => ['required', 'integer', 'min:0', 'max:120'],
            'commercialStatus' => ['required', 'in:active,made_to_order,discontinued'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        $product = $service->save(
            $actor,
            $this->productId ? Product::query()->findOrFail($this->productId) : null,
            [
                'sku' => strtoupper(trim($this->sku)),
                'name' => trim($this->name),
                'model' => trim($this->model) ?: null,
                'product_category_id' => (int) $this->categoryId,
                'product_brand_id' => $this->brandId !== '' ? (int) $this->brandId : null,
                'description' => trim($this->description) ?: null,
                'unit' => trim($this->unit),
                'standard_price' => $this->standardPrice,
                'vat_percent' => $this->vatPercent,
                'warranty_months' => (int) $this->warrantyMonths,
                'commercial_status' => $this->commercialStatus,
                'specifications' => ['usage_area' => trim($this->usageArea)],
                'is_active' => $this->isActive,
            ],
        );

        session()->flash('success', 'Đã lưu mẫu sản phẩm. Tiếp tục thêm biến thể bán hàng.');

        return $this->redirect(route('products.show', $product), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.products.product-editor', [
            'categories' => ProductCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'brands' => ProductBrand::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
