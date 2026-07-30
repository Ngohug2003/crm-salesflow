<?php

declare(strict_types=1);

namespace App\Livewire\PriceBooks;

use App\Models\PriceBook;
use App\Models\PriceBookEntry;
use App\Models\User;
use App\Services\PriceBookService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class PriceBookDetail extends Component
{
    public int $priceBookId;

    public PriceBook $priceBook;

    public bool $showEntryEditor = false;

    public ?int $editingEntryId = null;

    public string $unitPrice = '0';

    public string $vatPercent = '10';

    public int $minQuantity = 1;

    public bool $isActive = true;

    public function mount(int $priceBookId): void
    {
        $this->priceBookId = $priceBookId;
        $this->reloadPriceBook();
    }

    public function openEditEntry(int $entryId): void
    {
        $entry = $this->priceBook->entries->firstWhere('id', $entryId);
        abort_unless($entry instanceof PriceBookEntry, 404);
        $this->authorize('update', $this->priceBook);

        $this->editingEntryId = $entry->id;
        $this->unitPrice = (string) $entry->unit_price;
        $this->vatPercent = (string) $entry->vat_percent;
        $this->minQuantity = $entry->min_quantity;
        $this->isActive = $entry->is_active;
        $this->showEntryEditor = true;
    }

    public function saveEntry(PriceBookService $service): void
    {
        $this->validate([
            'unitPrice' => ['required', 'numeric', 'min:0'],
            'vatPercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'minQuantity' => ['required', 'integer', 'min:1'],
        ]);

        $entry = PriceBookEntry::query()->findOrFail($this->editingEntryId);
        /** @var User $actor */
        $actor = Auth::user();

        $service->saveEntry($actor, $this->priceBook, $entry, [
            'product_id' => $entry->product_id,
            'unit_price' => $this->unitPrice,
            'vat_percent' => $this->vatPercent,
            'min_quantity' => $this->minQuantity,
            'is_active' => $this->isActive,
        ]);

        $this->showEntryEditor = false;
        $this->reloadPriceBook();
        session()->flash('success', 'Đã cập nhật dòng giá.');
    }

    public function reloadPriceBook(): void
    {
        $this->priceBook = PriceBook::query()
            ->with(['entries.product'])
            ->findOrFail($this->priceBookId);
        $this->authorize('view', $this->priceBook);
    }

    public function render(): View
    {
        return view('livewire.price-books.price-book-detail');
    }
}
