<?php

declare(strict_types=1);

namespace App\Livewire\PriceBooks;

use App\Models\PriceBook;
use App\Models\Product;
use App\Models\User;
use App\Repositories\EloquentPriceBookRepository;
use App\Repositories\EloquentProductCatalogRepository;
use App\Services\PriceBookService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class PriceBookList extends Component
{
    public bool $showEditor = false;

    public bool $showEntryEditor = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $customerSegment = '';

    public string $region = '';

    public string $effectiveFrom = '';

    public string $effectiveUntil = '';

    public bool $isActive = true;

    public ?int $selectedBookId = null;

    public string $productId = '';

    public string $unitPrice = '0';

    public string $vatPercent = '10';

    public int $minQuantity = 1;

    public function updatedProductId(): void
    {
        if ($this->productId === '') {
            return;
        }

        $product = Product::query()->findOrFail($this->productId);
        $this->authorize('view', $product);
        $this->unitPrice = (string) $product->standard_price;
        $this->vatPercent = (string) $product->vat_percent;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'customerSegment', 'region', 'effectiveFrom', 'effectiveUntil']);
        $this->isActive = true;
        $this->showEditor = true;
    }

    public function openEdit(int $id): void
    {
        $book = PriceBook::query()->findOrFail($id);
        $this->authorize('update', $book);
        $this->editingId = $id;
        $this->name = $book->name;
        $this->customerSegment = $book->customer_segment ?? '';
        $this->region = $book->region ?? '';
        $this->effectiveFrom = (string) $book->effective_from?->format('Y-m-d');
        $this->effectiveUntil = (string) $book->effective_until?->format('Y-m-d');
        $this->isActive = $book->is_active;
        $this->showEditor = true;
    }

    public function save(PriceBookService $service): void
    {
        $this->validate(['name' => ['required', 'string', 'max:255'], 'effectiveFrom' => ['nullable', 'date'], 'effectiveUntil' => ['nullable', 'date', 'after_or_equal:effectiveFrom']]);
        /** @var User $actor */ $actor = Auth::user();
        $service->save($actor, $this->editingId ? PriceBook::query()->findOrFail($this->editingId) : null, ['name' => $this->name, 'customer_segment' => $this->customerSegment ?: null, 'region' => $this->region ?: null, 'currency_code' => 'VND', 'effective_from' => $this->effectiveFrom ?: null, 'effective_until' => $this->effectiveUntil ?: null, 'is_active' => $this->isActive]);
        $this->showEditor = false;
        session()->flash('success', 'Đã lưu bảng giá.');
    }

    public function openEntryEditor(int $bookId): void
    {
        $book = PriceBook::query()->findOrFail($bookId);
        $this->authorize('update', $book);
        $this->selectedBookId = $bookId;
        $this->reset(['productId', 'unitPrice']);
        $this->vatPercent = '10';
        $this->minQuantity = 1;
        $this->showEntryEditor = true;
    }

    public function saveEntry(PriceBookService $service): void
    {
        $this->validate(['productId' => ['required', 'integer', 'exists:products,id'], 'unitPrice' => ['required', 'numeric', 'min:0'], 'vatPercent' => ['required', 'numeric', 'min:0', 'max:100'], 'minQuantity' => ['required', 'integer', 'min:1']]);
        /** @var User $actor */ $actor = Auth::user();
        $book = PriceBook::query()->findOrFail($this->selectedBookId);
        $service->saveEntry($actor, $book, null, ['product_id' => $this->productId, 'unit_price' => $this->unitPrice, 'vat_percent' => $this->vatPercent, 'min_quantity' => $this->minQuantity, 'is_active' => true]);
        $this->showEntryEditor = false;
        session()->flash('success', 'Đã thêm dòng giá.');
    }

    public function render(
        EloquentPriceBookRepository $priceBooks,
        EloquentProductCatalogRepository $products,
    ): View {
        $this->authorize('viewAny', PriceBook::class);

        /** @var User $actor */
        $actor = Auth::user();

        return view('livewire.price-books.price-book-list', [
            'books' => $priceBooks->allVisible($actor),
            'products' => $products->activeVisible($actor),
        ])->layout('layouts.app', ['title' => 'Bảng giá']);
    }
}
