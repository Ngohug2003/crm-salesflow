<section class="crm-card">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Danh mục Sản phẩm & Dịch vụ ({{ $items->count() }})</h2>
            <!-- <p class="mt-1 text-sm text-slate-500">Đơn giá và VAT bên dưới là snapshot đã áp dụng cho cơ hội này; thay đổi catalog hoặc bảng giá sau đó không làm thay đổi dòng đã lưu.</p> -->
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
                <flux:table.column>Nguồn giá</flux:table.column>
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
                                <p class="mt-1 text-xs font-normal text-slate-500">VAT áp dụng: {{ number_format((float) $item->vat_percent, 0, ',', '.') }}%</p>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($item->priceBookEntry?->priceBook)
                                <div class="space-y-1">
                                    <flux:badge size="sm" color="indigo">Bảng giá</flux:badge>
                                    <p class="text-xs text-slate-500">{{ $item->priceBookEntry->priceBook->name }}</p>
                                </div>
                            @elseif ($item->product)
                                <div class="space-y-1">
                                    <flux:badge size="sm" color="zinc">Catalog</flux:badge>
                                    <p class="text-xs text-slate-500">Giá chuẩn tại lúc chọn</p>
                                </div>
                            @else
                                <span class="text-sm text-slate-400">Nhập thủ công</span>
                            @endif
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
                        <flux:table.cell colspan="8" class="text-center py-6 text-slate-500">Chưa có dòng sản phẩm/dịch vụ nào. Hãy bấm "Thêm sản phẩm" để khai báo chi tiết.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showModal" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading>{{ $editingItemId ? 'Chỉnh sửa sản phẩm/dịch vụ' : 'Thêm sản phẩm/dịch vụ mới' }}</flux:heading>
                <flux:subheading>Chọn catalog và bảng giá trước để hệ thống tự áp dụng đơn giá, VAT. Anh vẫn có thể chỉnh từng dòng trước khi lưu.</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-forms.smart-select wire:model.live="priceBookId" label="Bảng giá">
                        <option value="">Giá chuẩn từ catalog</option>
                        @foreach ($priceBooks as $priceBook)
                            <option value="{{ $priceBook->id }}">{{ $priceBook->name }}</option>
                        @endforeach
                    </x-forms.smart-select>
                    <flux:field>
                        <flux:label>Sản phẩm từ catalog</flux:label>
                        <x-forms.modal-searchable-select
                            wire:model.live="productId"
                            placeholder="Nhập thủ công"
                            search-placeholder="Tìm theo SKU hoặc tên sản phẩm…"
                            empty-message="Không tìm thấy sản phẩm phù hợp."
                        >
                            <option value="">Nhập thủ công</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}</option>
                            @endforeach
                        </x-forms.modal-searchable-select>
                        <flux:error name="productId" />
                    </flux:field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="productName" label="Tên sản phẩm / Dịch vụ *" placeholder="Ví dụ: Gói giải pháp SalesFlow Pro" required />
                    <flux:input wire:model="sku" label="Mã SKU" placeholder="Ví dụ: SF-PRO-01" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-forms.money-input model="unitPrice" label="Đơn giá" required />
                    <flux:input wire:model="quantity" type="number" min="1" label="Số lượng *" required />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="discountPercent" type="number" step="0.1" min="0" max="100" label="Khuyến mãi (%)" placeholder="0" />
                    <flux:input wire:model="vatPercent" type="number" step="0.1" min="0" max="100" label="VAT (%)" placeholder="10" />
                </div>
                <flux:textarea wire:model="notes" label="Ghi chú" placeholder="Nội dung ghi chú hoặc diễn giải..." rows="2" />
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
                <flux:button wire:click="$set('showModal', false)" variant="ghost" type="button">Hủy</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">Lưu thay đổi</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
