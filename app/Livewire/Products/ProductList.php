<?php

declare(strict_types=1);

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSupplier;
use App\Repositories\EloquentProductCatalogRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class ProductList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $categoryId = '';

    #[Url(as: 'supplier', except: '')]
    public string $supplierId = '';

    #[Url(except: '')]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedSupplierId(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'categoryId', 'supplierId', 'status']);
        $this->resetPage();
    }

    public function render(EloquentProductCatalogRepository $repository): View
    {
        $this->authorize('viewAny', Product::class);

        return view('livewire.products.product-list', [
            'products' => $repository->paginateVisible(
                Auth::user(),
                trim($this->search),
                $this->categoryId !== '' ? (int) $this->categoryId : null,
                $this->supplierId !== '' ? (int) $this->supplierId : null,
                $this->status,
            ),
            'categories' => ProductCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'suppliers' => ProductSupplier::query()->where('is_active', true)->orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => 'Danh mục nội thất']);
    }
}
