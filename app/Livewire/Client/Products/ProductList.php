<?php

declare(strict_types=1);

namespace App\Livewire\Client\Products;

use App\Models\ProductCategory;
use App\Repositories\EloquentProductCatalogRepository;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.client')]
final class ProductList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $categoryId = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'categoryId']);
        $this->resetPage();
    }

    public function render(EloquentProductCatalogRepository $repository): View
    {
        return view('livewire.client.products.product-list', [
            'products' => $repository->paginatePublic(
                $this->search,
                $this->categoryId !== '' ? (int) $this->categoryId : null,
            ),
            'categories' => ProductCategory::query()
                ->where('is_active', true)
                ->whereHas('products', fn ($query) => $query->where('is_active', true)->where('commercial_status', '!=', 'discontinued'))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
