<div class="space-y-5">
    <div class="data-list-heading">
        <div>
            <h1 class="text-xl font-semibold">Danh mục nội thất văn phòng</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Quản lý mẫu sản phẩm, biến thể cấu hình và nguồn cung phục vụ bán hàng B2B.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('products.update')
                <flux:button href="{{ route('products.catalog-settings') }}" wire:navigate variant="ghost" icon="cog-6-tooth">
                    Danh mục & nhà cung cấp
                </flux:button>
            @endcan
            @can('create', \App\Models\Product::class)
                <flux:button href="{{ route('products.create') }}" wire:navigate variant="primary" icon="plus">
                    Thêm mẫu sản phẩm
                </flux:button>
            @endcan
        </div>
    </div>

    <section class="crm-card">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                label="Tìm kiếm"
                placeholder="Tên hoặc mã sản phẩm"
                icon="magnifying-glass"
            />

            <x-forms.smart-select wire:model.live="categoryId" label="Danh mục">
                <option value="">Tất cả danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </x-forms.smart-select>

            <x-forms.smart-select wire:model.live="supplierId" label="Nhà cung cấp">
                <option value="">Tất cả nhà cung cấp</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </x-forms.smart-select>

            <x-forms.smart-select wire:model.live="status" label="Trạng thái">
                <option value="">Tất cả trạng thái</option>
                <option value="active">Đang kinh doanh</option>
                <option value="made_to_order">Sản xuất theo đơn</option>
                <option value="discontinued">Ngừng kinh doanh</option>
            </x-forms.smart-select>
        </div>

        @if ($search !== '' || $categoryId !== '' || $supplierId !== '' || $status !== '')
            <div class="mt-3">
                <flux:button wire:click="resetFilters" size="sm" variant="ghost" icon="x-mark">
                    Xóa bộ lọc
                </flux:button>
            </div>
        @endif
    </section>

    <section class="crm-card overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Sản phẩm</flux:table.column>
                <flux:table.column>Phân loại</flux:table.column>
                <flux:table.column>Biến thể</flux:table.column>
                <flux:table.column align="end">Giá tham khảo</flux:table.column>
                <flux:table.column>Trạng thái</flux:table.column>
                <flux:table.column />
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($products as $product)
                    <flux:table.row :key="$product->id">
                        <flux:table.cell variant="strong">
                            <div class="flex min-w-64 items-center gap-3">
                                <a href="{{ route('products.show', $product->id) }}" wire:navigate class="shrink-0">
                                    @if ($product->primaryMedia)
                                        <img
                                            src="{{ route('products.media.show', [$product->id, $product->primaryMedia->id]) }}"
                                            alt="{{ $product->name }}"
                                            loading="lazy"
                                            class="h-12 w-12 rounded-lg border border-slate-200 object-cover dark:border-slate-700"
                                        />
                                    @else
                                        <span class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-900">
                                            <flux:icon.photo class="size-5" />
                                        </span>
                                    @endif
                                </a>
                                <div class="min-w-0">
                                    <a
                                        href="{{ route('products.show', $product->id) }}"
                                        wire:navigate
                                        class="hover:text-emerald-600 dark:hover:text-emerald-400"
                                    >
                                        {{ $product->name }}
                                    </a>
                                    <div class="font-mono text-xs font-normal text-slate-500">
                                        {{ $product->sku }}{{ $product->model ? ' · '.$product->model : '' }}
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $product->category?->name ?: 'Chưa phân loại' }}
                            <div class="text-xs text-slate-500">{{ $product->brand?->name ?: 'Chưa có hãng' }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $product->variants_count }} cấu hình
                            <div class="text-xs text-slate-500">{{ $product->unit }}</div>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            {{ number_format((float) $product->standard_price, 0, ',', '.') }} ₫
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $statusMap = [
                                    'active' => ['Đang kinh doanh', 'emerald'],
                                    'made_to_order' => ['Sản xuất theo đơn', 'amber'],
                                    'discontinued' => ['Ngừng kinh doanh', 'zinc'],
                                ];
                                [$statusLabel, $statusColor] = $statusMap[$product->commercial_status]
                                    ?? [$product->commercial_status, 'zinc'];
                            @endphp
                            <flux:badge :color="$statusColor">{{ $statusLabel }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    href="{{ route('products.show', $product->id) }}"
                                    wire:navigate
                                >
                                    Xem
                                </flux:button>
                                @can('update', $product)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        href="{{ route('products.edit', $product->id) }}"
                                        wire:navigate
                                    >
                                        Sửa
                                    </flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <div class="py-10 text-center">
                                <p class="font-medium">Chưa có sản phẩm phù hợp</p>
                                <p class="mt-1 text-sm text-slate-500">Thử đổi bộ lọc hoặc thêm mẫu sản phẩm đầu tiên.</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">{{ $products->links() }}</div>
    </section>
</div>
