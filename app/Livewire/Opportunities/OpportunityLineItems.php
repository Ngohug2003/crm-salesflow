<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Models\Opportunity;
use App\Models\OpportunityItem;
use App\Models\User;
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

    public function mount(int $opportunityId): void
    {
        $this->opportunityId = $opportunityId;
    }

    public function openCreate(): void
    {
        $this->reset(['editingItemId', 'productName', 'sku', 'unitPrice', 'quantity', 'discountPercent', 'notes']);
        $this->unitPrice = '0';
        $this->quantity = 1;
        $this->discountPercent = '0';
        $this->showModal = true;
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
            'sku' => $this->sku !== '' ? $this->sku : null,
            'unit_price' => (float) $this->unitPrice,
            'quantity' => $this->quantity,
            'discount_percent' => (float) ($this->discountPercent !== '' ? $this->discountPercent : 0),
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
            ->orderBy('id')
            ->get();

        return $collection;
    }

    public function render(): View
    {
        /** @var Opportunity $opp */
        $opp = Opportunity::query()->findOrFail($this->opportunityId);

        return view('livewire.opportunities.opportunity-line-items', [
            'items' => $this->items(),
            'opportunity' => $opp,
        ]);
    }
}
