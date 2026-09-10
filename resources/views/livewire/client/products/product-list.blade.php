<div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl bg-slate-900 px-6 py-10 text-white shadow-sm sm:px-10 lg:py-14">
        <div class="relative z-10 max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">Furniture collection</p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Không gian làm việc được thiết kế để phát triển</h1>
            <p class="mt-4 max-w-xl text-sm leading-6 text-slate-300 sm:text-base">Khám phá các mẫu bàn, ghế, tủ và phụ kiện nội thất văn phòng. Mỗi cấu hình có thể điều chỉnh theo mặt bằng và nhu cầu dự án.</p>
        </div>
        <div class="pointer-events-none absolute -right-20 -top-24 h-80 w-80 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 right-20 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
    </section>

    <section class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight">Danh mục sản phẩm</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Chọn một mẫu để xem cấu hình, vật liệu và giá tham khảo.</p>
            </div>
            <span class="text-sm text-slate-500">{{ $products->total() }} sản phẩm</span>
        </div>

        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_16rem_auto]">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Tìm tên hoặc mã sản phẩm…" icon="magnifying-glass" aria-label="Tìm sản phẩm" />
            <x-forms.smart-select wire:model.live="categoryId" label="Danh mục">
                <option value="">Tất cả danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </x-forms.smart-select>
            @if ($search !== '' || $categoryId !== '')
                <flux:button wire:click="resetFilters" variant="ghost" icon="x-mark" class="self-end">Xóa lọc</flux:button>
            @endif
        </div>
    </section>

    <section>
        @if ($products->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                <flux:icon.magnifying-glass class="mx-auto size-8 text-slate-400" />
                <p class="mt-3 font-medium">Không tìm thấy sản phẩm</p>
                <p class="mt-1 text-sm text-slate-500">Thử đổi từ khóa hoặc danh mục đang chọn.</p>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <a href="{{ route('client.products.show', $product->id) }}" wire:navigate wire:key="client-product-{{ $product->id }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-700">
                        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-slate-800">
                            @if ($product->primaryMedia)
                                <img src="{{ route('client.products.media', [$product->id, $product->primaryMedia->id]) }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                            @else
                                <div class="flex h-full items-center justify-center text-slate-400"><flux:icon.photo class="size-10" /></div>
                            @endif
                            @if ($product->category)
                                <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-medium text-slate-700 shadow-sm dark:bg-slate-900/90 dark:text-slate-200">{{ $product->category->name }}</span>
                            @endif
                        </div>
                        <div class="space-y-3 p-4">
                            <div>
                                <p class="font-mono text-[11px] text-slate-500">{{ $product->sku }}</p>
                                <h3 class="mt-1 line-clamp-2 font-semibold group-hover:text-emerald-700 dark:group-hover:text-emerald-400">{{ $product->name }}</h3>
                            </div>
                            <div class="flex items-end justify-between gap-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                                <div><p class="text-[11px] text-slate-500">Giá tham khảo</p><p class="font-semibold text-emerald-700 dark:text-emerald-400">{{ number_format((float) $product->standard_price, 0, ',', '.') }} ₫</p></div>
                                <span class="text-xs text-slate-500">{{ $product->variants_count }} cấu hình</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $products->links() }}</div>
        @endif
    </section>
</div>
