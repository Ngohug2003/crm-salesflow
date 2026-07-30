<?php

declare(strict_types=1);

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\User;
use App\Repositories\EloquentProductCatalogRepository;
use App\Services\ProductCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

final class ProductList extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showEditor = false;

    public ?int $editingId = null;

    public string $sku = '';

    public string $name = '';

    public string $description = '';

    public string $unit = 'Đơn vị';

    public string $standardPrice = '0';

    public string $vatPercent = '10';

    public bool $isActive = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'sku', 'name', 'description', 'standardPrice']);
        $this->unit = 'Đơn vị';
        $this->vatPercent = '10';
        $this->isActive = true;
        $this->showEditor = true;
    }

    public function openEdit(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        $this->authorize('update', $product);
        $this->editingId = $id;
        $this->sku = $product->sku;
        $this->name = $product->name;
        $this->description = $product->description ?? '';
        $this->unit = $product->unit;
        $this->standardPrice = (string) $product->standard_price;
        $this->vatPercent = (string) $product->vat_percent;
        $this->isActive = $product->is_active;
        $this->showEditor = true;
    }

    public function save(ProductCatalogService $service): void
    {
        $this->validate(['sku' => ['required', 'string', 'max:80'], 'name' => ['required', 'string', 'max:255'], 'unit' => ['required', 'string', 'max:50'], 'standardPrice' => ['required', 'numeric', 'min:0'], 'vatPercent' => ['required', 'numeric', 'min:0', 'max:100']]);
        /** @var User $actor */ $actor = Auth::user();
        $service->save($actor, $this->editingId ? Product::query()->findOrFail($this->editingId) : null, ['sku' => strtoupper(trim($this->sku)), 'name' => trim($this->name), 'description' => $this->description ?: null, 'unit' => trim($this->unit), 'standard_price' => $this->standardPrice, 'vat_percent' => $this->vatPercent, 'is_active' => $this->isActive]);
        $this->showEditor = false;
        session()->flash('success', 'Đã lưu sản phẩm.');
    }

    public function render(EloquentProductCatalogRepository $products): View
    {
        $this->authorize('viewAny', Product::class);
        $productPage = $products->paginateVisible(Auth::user(), trim($this->search));

        return view('livewire.products.product-list', ['products' => $productPage])
            ->layout('layouts.app', ['title' => 'Danh mục sản phẩm']);
    }
}
