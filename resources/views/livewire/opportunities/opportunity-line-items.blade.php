<section class="crm-card">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Danh mục Sản phẩm & Dịch vụ ({{ $items->count() }})</h2>
            <p class="mt-1 text-sm text-slate-500">Khai báo chi tiết các sản phẩm, số lượng, đơn giá và chiết khấu thuộc cơ hội bán hàng này.</p>
        </div>
        @can('update', $opportunity)
            <flux:button wire:click="openCreate" variant="primary" size="sm" icon="plus">
                Thêm sản phẩm
            </flux:button>
        @endcan
    </div>

    <div class="overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Sản phẩm / Dịch vụ</flux:table.column>
                <flux:table.column>SKU</flux:table.column>
                <flux:table.column align="end">Đơn giá</flux:table.column>
                <flux:table.column align="end">SL</flux:table.column>
                <flux:table.column align="end">Khuyến mãi</flux:table.column>
                <flux:table.column align="end">Thành tiền</flux:table.column>
                @can('update', $opportunity)
                    <flux:table.column align="end">Thao tác</flux:table.column>
                @endcan
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($items as $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell variant="strong">
                            <div>
                                <span class="text-slate-900 dark:text-white font-medium">{{ $item->product_name }}</span>
                                @if ($item->notes)
                                    <p class="text-xs text-slate-400 font-normal mt-0.5">{{ $item->notes }}</p>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell><span class="font-mono text-xs text-slate-500">{{ $item->sku ?: '—' }}</span></flux:table.cell>
                        <flux:table.cell align="end">{{ number_format((float) $item->unit_price, 0, ',', '.') }} ₫</flux:table.cell>
                        <flux:table.cell align="end"><span class="font-semibold">{{ $item->quantity }}</span></flux:table.cell>
                        <flux:table.cell align="end">
                            @if ((float) $item->discount_percent > 0)
                                <flux:badge color="amber" size="sm">{{ (float) $item->discount_percent }}%</flux:badge>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                {{ number_format((float) $item->total_price, 0, ',', '.') }} ₫
                            </span>
                        </flux:table.cell>
                        @can('update', $opportunity)
                            <flux:table.cell align="end">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button wire:click="openEdit({{ $item->id }})" size="xs" variant="ghost" icon="pencil-square" />
                                    <flux:button wire:click="deleteItem({{ $item->id }})" wire:confirm="Bạn có chắc chắn muốn xóa dòng sản phẩm này không?" size="xs" variant="ghost" color="red" icon="trash" />
                                </div>
                            </flux:table.cell>
                        @endcan
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-6 text-slate-500">Chưa có dòng sản phẩm/dịch vụ nào. Hãy bấm "Thêm sản phẩm" để khai báo chi tiết.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <!-- Modal Create/Edit Item -->
    <div
        x-data="{ open: @entangle('showModal') }"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4"
        >
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ $editingItemId ? 'Chỉnh sửa sản phẩm/dịch vụ' : 'Thêm sản phẩm/dịch vụ mới' }}
                    </h3>
                    <p class="mt-1 text-xs text-slate-500">Khai báo đơn giá, số lượng và phần trăm chiết khấu để tính doanh thu tự động.</p>
                </div>

                <div class="space-y-3">
                    <flux:input wire:model="productName" label="Tên sản phẩm / Dịch vụ *" placeholder="Ví dụ: Gói giải pháp SalesFlow Pro" required />
                    <flux:input wire:model="sku" label="Mã SKU (tùy chọn)" placeholder="Ví dụ: SF-PRO-01" />

                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:input wire:model="unitPrice" type="number" label="Đơn giá (VNĐ) *" placeholder="10000000" required />
                        <flux:input wire:model="quantity" type="number" min="1" label="Số lượng *" required />
                    </div>

                    <flux:input wire:model="discountPercent" type="number" step="0.1" min="0" max="100" label="Khuyến mãi (%)" placeholder="0" />
                    <flux:textarea wire:model="notes" label="Ghi chú thêm" placeholder="Nội dung ghi chú hoặc diễn giải..." rows="2" />
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <flux:button wire:click="$set('showModal', false)" variant="ghost" size="sm" type="button">
                        Hủy
                    </flux:button>
                    <flux:button type="submit" variant="primary" size="sm" wire:loading.attr="disabled">
                        Lưu thay đổi
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</section>
