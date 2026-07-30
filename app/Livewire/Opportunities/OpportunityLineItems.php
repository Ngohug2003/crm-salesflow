<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Models\Opportunity;
use App\Models\OpportunityItem;
use App\Models\PriceBook;
use App\Models\PriceBookEntry;
use App\Models\Product;
use App\Models\User;
use App\Repositories\EloquentPriceBookRepository;
use App\Repositories\EloquentProductCatalogRepository;
use App\Services\OpportunityItemService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class OpportunityLineItems extends Component
{
    public int $opportunityId;

    public bool $showModal = false;

    public ?int $editingItemId = null;

    public string $productName = '';

    public string $sku = '';

    public string $unitPrice = '0';

    public int $quantity = 1;

    public string $discountPercent = '0';

    public string $notes = '';

    public string $productId = '';

    public string $priceBookId = '';

    public ?int $priceBookEntryId = null;

    public string $vatPercent = '0';

    public function mount(int $opportunityId): void
    {
        $this->opportunityId = $opportunityId;
    }

    public function openCreate(): void
    {
        $this->reset(['editingItemId', 'productName', 'sku', 'unitPrice', 'quantity', 'discountPercent', 'notes', 'productId', 'priceBookId', 'priceBookEntryId', 'vatPercent']);
        $this->unitPrice = '0';
        $this->quantity = 1;
        $this->discountPercent = '0';
        $this->showModal = true;
    }

    public function updatedProductId(): void
    {
        $product = $this->productId === '' ? null : Product::query()->find($this->productId);
        if ($product === null) {
            return;
        }

        $this->authorize('view', $product);

        $this->productName = $product->name;
        $this->sku = $product->sku;
        $this->unitPrice = (string) $product->standard_price;
        $this->vatPercent = (string) $product->vat_percent;
        $this->priceBookEntryId = null;

        if ($this->priceBookId !== '') {
            $priceBook = PriceBook::query()->findOrFail($this->priceBookId);
            $this->authorize('view', $priceBook);

            $entry = PriceBookEntry::query()
                ->usable()
                ->where('price_book_id', $this->priceBookId)
                ->where('product_id', $product->id)
                ->where('min_quantity', '<=', $this->quantity)
                ->orderByDesc('min_quantity')
                ->first();

            if ($entry !== null) {
                $this->unitPrice = (string) $entry->unit_price;
                $this->vatPercent = (string) $entry->vat_percent;
                $this->priceBookEntryId = $entry->id;
            }
        }

        $this->dispatch('salesflow-money-input-updated', model: 'unitPrice', value: $this->unitPrice);
    }

    public function updatedPriceBookId(): void
    {
        if ($this->productId !== '') {
            $this->updatedProductId();
        }
    }

    public function openEdit(int $itemId): void
    {
        /** @var OpportunityItem $item */
        $item = OpportunityItem::query()->findOrFail($itemId);

        $this->editingItemId = $item->id;
        $this->productName = $item->product_name;
        $this->sku = $item->sku ?? '';
        $this->unitPrice = (string) $item->unit_price;
        $this->quantity = $item->quantity;
        $this->discountPercent = (string) $item->discount_percent;
        $this->vatPercent = (string) $item->vat_percent;
        $this->productId = $item->product_id === null ? '' : (string) $item->product_id;
        $this->priceBookEntryId = $item->price_book_entry_id;
        $this->notes = $item->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'productName' => ['required', 'string', 'max:255'],
            'unitPrice' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'discountPercent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vatPercent' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'productName.required' => 'Vui lòng nhập tên sản phẩm/dịch vụ.',
            'unitPrice.required' => 'Vui lòng nhập đơn giá.',
            'quantity.required' => 'Vui lòng nhập số lượng tối thiểu là 1.',
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityItemService $service */
        $service = app(OpportunityItemService::class);

        $payload = [
            'product_name' => $this->productName,
            'product_id' => $this->productId !== '' ? (int) $this->productId : null,
            'price_book_entry_id' => $this->priceBookEntryId,
            'sku' => $this->sku !== '' ? $this->sku : null,
            'unit_price' => (float) $this->unitPrice,
            'quantity' => $this->quantity,
            'discount_percent' => (float) ($this->discountPercent !== '' ? $this->discountPercent : 0),
            'vat_percent' => (float) $this->vatPercent,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];

        if ($this->editingItemId !== null) {
            $service->updateItem($actor, $this->editingItemId, $payload);
        } else {
            $service->addItem($actor, $this->opportunityId, $payload);
        }

        $this->showModal = false;
        $this->dispatch('attachment-updated');
    }

    public function deleteItem(int $itemId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityItemService $service */
        $service = app(OpportunityItemService::class);

        $service->deleteItem($actor, $itemId);
        $this->dispatch('attachment-updated');
    }

    /**
     * @return EloquentCollection<int, OpportunityItem>
     */
    #[Computed]
    public function items(): EloquentCollection
    {
        /** @var EloquentCollection<int, OpportunityItem> $collection */
        $collection = OpportunityItem::query()
            ->where('opportunity_id', $this->opportunityId)
            ->with(['product', 'priceBookEntry.priceBook'])
            ->orderBy('id')
            ->get();

        return $collection;
    }

    public function render(
        EloquentProductCatalogRepository $products,
        EloquentPriceBookRepository $priceBooks,
    ): View {
        /** @var Opportunity $opp */
        $opp = Opportunity::query()->findOrFail($this->opportunityId);

        return view('livewire.opportunities.opportunity-line-items', [
            'items' => $this->items(),
            'opportunity' => $opp,
            'products' => $products->activeVisible(Auth::user()),
            'priceBooks' => $priceBooks->usableVisible(Auth::user()),
        ]);
    }
}
