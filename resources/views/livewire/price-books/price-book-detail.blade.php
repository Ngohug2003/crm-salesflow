<div class="space-y-5">
    <div class="data-list-heading">
        <div>
            <div class="mb-2 text-xs font-medium text-slate-500">
                <a wire:navigate href="{{ route('price-books.index') }}" class="hover:text-emerald-600">Bảng giá</a> / Chi tiết
            </div>
            <h1 class="text-xl font-semibold text-slate-950 dark:text-white">{{ $priceBook->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $priceBook->customer_segment ?: 'Mọi phân khúc' }} · {{ $priceBook->region ?: 'Mọi khu vực' }} · {{ $priceBook->currency_code }}
            </p>
        </div>
        <flux:button href="{{ route('price-books.index') }}" wire:navigate variant="ghost">Quay lại</flux:button>
    </div>

    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-100">
        <strong>Cách áp dụng giá:</strong> Giá chuẩn thuộc sản phẩm. Khi Sale chọn bảng giá này, mức giá theo sản phẩm ở đây sẽ ghi đè giá chuẩn; dòng Opportunity/Báo giá sau khi lưu vẫn giữ snapshot giá riêng.
    </div>

    <section class="crm-card overflow-x-auto">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-950 dark:text-white">Sản phẩm và mức giá</h2>
                <p class="mt-1 text-sm text-slate-500">Mỗi sản phẩm có thể có nhiều mức theo số lượng tối thiểu.</p>
            </div>
            <flux:badge :color="$priceBook->is_active ? 'emerald' : 'zinc'">{{ $priceBook->is_active ? 'Đang áp dụng' : 'Ngừng áp dụng' }}</flux:badge>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Sản phẩm</flux:table.column>
                <flux:table.column align="end">Giá chuẩn</flux:table.column>
                <flux:table.column align="end">Giá bảng giá</flux:table.column>
                <flux:table.column align="end">VAT</flux:table.column>
                <flux:table.column align="end">SL tối thiểu</flux:table.column>
                <flux:table.column>Trạng thái</flux:table.column>
                <flux:table.column />
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($priceBook->entries as $entry)
                    <flux:table.row :key="$entry->id">
                        <flux:table.cell variant="strong">
                            {{ $entry->product->name }}
                            <div class="mt-0.5 font-mono text-xs font-normal text-slate-500">{{ $entry->product->sku }} · {{ $entry->product->unit }}</div>
                        </flux:table.cell>
                        <flux:table.cell align="end">{{ number_format((float) $entry->product->standard_price, 0, ',', '.') }} ₫</flux:table.cell>
                        <flux:table.cell align="end"><span class="font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format((float) $entry->unit_price, 0, ',', '.') }} ₫</span></flux:table.cell>
                        <flux:table.cell align="end">{{ number_format((float) $entry->vat_percent, 0, ',', '.') }}%</flux:table.cell>
                        <flux:table.cell align="end">{{ $entry->min_quantity }}</flux:table.cell>
                        <flux:table.cell><flux:badge :color="$entry->is_active ? 'emerald' : 'zinc'" size="sm">{{ $entry->is_active ? 'Đang dùng' : 'Ngừng dùng' }}</flux:badge></flux:table.cell>
                        <flux:table.cell align="end">
                            @can('update', $priceBook)
                                <flux:button wire:click="openEditEntry({{ $entry->id }})" size="sm" variant="ghost">Sửa giá</flux:button>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="7" class="py-10 text-center text-slate-500">Bảng giá chưa có sản phẩm. Quay lại danh sách để thêm dòng giá.</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </section>

    <flux:modal wire:model="showEntryEditor" class="w-full max-w-lg">
        <form wire:submit="saveEntry" class="space-y-4">
            <div>
                <flux:heading>Cập nhật mức giá</flux:heading>
                <flux:subheading>Thay đổi này chỉ áp dụng cho các Opportunity/Báo giá tạo sau khi lưu.</flux:subheading>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-forms.money-input model="unitPrice" label="Đơn giá" required />
                <flux:input wire:model="vatPercent" type="number" min="0" max="100" step="0.1" label="VAT (%)" required />
                <flux:input wire:model="minQuantity" type="number" min="1" label="Số lượng tối thiểu" required />
            </div>
            <flux:checkbox wire:model="isActive" label="Đang áp dụng" />
            <div class="flex justify-end gap-2"><flux:button type="button" wire:click="$set('showEntryEditor', false)" variant="ghost">Hủy</flux:button><flux:button type="submit" variant="primary">Lưu mức giá</flux:button></div>
        </form>
    </flux:modal>
</div>
