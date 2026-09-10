<div class="space-y-6">
    <a href="{{ route('client.products.index') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-emerald-700 dark:hover:text-emerald-400">
        <flux:icon.arrow-left class="size-4" /> Quay lại danh mục
    </a>

    <div class="grid gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(22rem,0.85fr)] lg:items-start">
        <section class="space-y-4">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                @if ($selectedMedia)
                    <img wire:key="selected-media-{{ $selectedMedia->id }}" src="{{ route('client.products.media', [$product->id, $selectedMedia->id]) }}" alt="{{ $product->name }}" class="aspect-[4/3] w-full object-contain transition-opacity duration-200" />
                    @if ($product->media->count() > 1)
                        <span class="absolute bottom-4 right-4 rounded-full bg-slate-900/70 px-3 py-1 text-xs text-white">{{ $product->media->search(fn ($media) => $media->id === $selectedMedia->id) + 1 }} / {{ $product->media->count() }}</span>
                    @endif
                @else
                    <div class="flex aspect-[4/3] items-center justify-center bg-slate-100 text-slate-400 dark:bg-slate-800"><flux:icon.photo class="size-14" /></div>
                @endif
            </div>
            @if ($product->media->count() > 1)
                <div class="grid grid-cols-4 gap-3 sm:grid-cols-6">
                    @foreach ($product->media as $media)
                        <button type="button" wire:click="selectMedia({{ $media->id }})" wire:key="media-thumb-{{ $media->id }}" class="overflow-hidden rounded-xl border-2 {{ $selectedMedia?->id === $media->id ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200 hover:border-emerald-300 dark:border-slate-700 dark:hover:border-emerald-700' }} bg-white transition dark:bg-slate-900" aria-label="Xem ảnh {{ $loop->iteration }}">
                            <img src="{{ route('client.products.media', [$product->id, $media->id]) }}" alt="{{ $media->original_name }}" loading="lazy" class="aspect-square w-full object-cover" />
                        </button>
                    @endforeach
                </div>
            @endif
            <div class="grid grid-cols-3 gap-2 text-center text-xs text-slate-500">
                <div class="rounded-xl bg-white px-2 py-3 dark:bg-slate-900"><flux:icon.shield-check class="mx-auto size-4 text-emerald-600" /><span class="mt-1 block">Bảo hành rõ ràng</span></div>
                <div class="rounded-xl bg-white px-2 py-3 dark:bg-slate-900"><flux:icon.swatch class="mx-auto size-4 text-emerald-600" /><span class="mt-1 block">Nhiều cấu hình</span></div>
                <div class="rounded-xl bg-white px-2 py-3 dark:bg-slate-900"><flux:icon.truck class="mx-auto size-4 text-emerald-600" /><span class="mt-1 block">Tư vấn giao hàng</span></div>
            </div>
        </section>

        <section class="space-y-6">
            <div>
                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    @if ($product->category)<span>{{ $product->category->name }}</span>@endif
                    @if ($product->brand)<span>· {{ $product->brand->name }}</span>@endif
                </div>
                <p class="mt-2 font-mono text-xs text-slate-500">{{ $product->sku }}{{ $product->model ? ' · '.$product->model : '' }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ $product->name }}</h1>
                <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $product->description ?: 'Thiết kế phù hợp cho không gian văn phòng hiện đại và các dự án nội thất B2B.' }}</p>
            </div>

            <div class="rounded-2xl bg-emerald-50 p-5 dark:bg-emerald-950/30">
                <p class="text-xs text-emerald-800 dark:text-emerald-300">Giá tham khảo từ</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ number_format((float) ($selectedVariant?->standard_price ?? $product->standard_price), 0, ',', '.') }} ₫ <span class="text-sm font-normal">/ {{ $selectedVariant?->unit ?? $product->unit }}</span></p>
                <p class="mt-1 text-xs text-emerald-800/70 dark:text-emerald-300/70">Giá chính thức sẽ được Sales tư vấn theo cấu hình và quy mô dự án.</p>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-800"><p class="text-xs text-slate-500">Bảo hành</p><p class="mt-1 font-semibold">{{ $selectedVariant?->warranty_months ?? $product->warranty_months }} tháng</p></div>
                <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-800"><p class="text-xs text-slate-500">Thời gian đáp ứng</p><p class="mt-1 font-semibold">{{ $selectedVariant?->lead_time_days ?? '—' }} ngày</p></div>
            </div>

            <a href="mailto:sales@salesflow.test?subject=Tư vấn {{ urlencode($product->name) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"><flux:icon.chat-bubble-left-right class="size-4" /> Liên hệ tư vấn sản phẩm</a>
        </section>
    </div>

    @if ($product->variants->isNotEmpty())
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-lg font-semibold">Chọn cấu hình</h2><p class="mt-1 text-sm text-slate-500">Chọn một cấu hình để xem giá và thông số tương ứng.</p></div><span class="text-xs text-slate-500">{{ $product->variants->count() }} lựa chọn</span></div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($product->variants as $variant)
                    <button type="button" wire:click="selectVariant({{ $variant->id }})" wire:key="client-variant-{{ $variant->id }}" class="rounded-xl border-2 p-4 text-left transition {{ $selectedVariant?->id === $variant->id ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/20' : 'border-slate-200 hover:border-emerald-300 dark:border-slate-700 dark:hover:border-emerald-700' }}">
                        <div class="flex items-start justify-between gap-2"><span class="font-semibold">{{ $variant->name ?: $variant->sku }}</span>@if ($variant->is_default)<flux:badge color="emerald" size="sm">Khuyên dùng</flux:badge>@endif</div>
                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $variant->sku }}</p>
                        <p class="mt-3 font-semibold text-emerald-700 dark:text-emerald-400">{{ number_format((float) $variant->standard_price, 0, ',', '.') }} ₫</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $variant->material ?: 'Chưa cập nhật vật liệu' }} · {{ $variant->color ?: 'Màu tùy chọn' }}</p>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    <section class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div>
            <h2 class="text-lg font-semibold">Thông số cấu hình đang chọn</h2>
            <p class="mt-1 text-sm text-slate-500">Thông tin được cập nhật theo Variant anh chọn ở phía trên.</p>
            @if ($selectedVariant)
                <dl class="mt-5 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">SKU bán hàng</dt><dd class="mt-1 font-mono font-medium">{{ $selectedVariant->sku }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Đơn vị</dt><dd class="mt-1 font-medium">{{ $selectedVariant->unit }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Kích thước</dt><dd class="mt-1">{{ $selectedVariant->length_mm ?: '—' }} × {{ $selectedVariant->width_mm ?: '—' }} × {{ $selectedVariant->height_mm ?: '—' }} mm</dd></div>
                    <div><dt class="text-xs text-slate-500">Vật liệu</dt><dd class="mt-1">{{ $selectedVariant->material ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Màu sắc</dt><dd class="mt-1">{{ $selectedVariant->color ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Hoàn thiện</dt><dd class="mt-1">{{ $selectedVariant->finish ?: '—' }}</dd></div>
                </dl>
            @else
                <p class="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-950">Sản phẩm đang được cập nhật cấu hình.</p>
            @endif
        </div>
        <div class="rounded-xl bg-slate-50 p-5 dark:bg-slate-950">
            <h3 class="font-semibold">Cần tư vấn theo mặt bằng?</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Sales sẽ hỗ trợ chọn cấu hình, số lượng, màu sắc và lập báo giá theo dự án của anh.</p>
            <a href="mailto:sales@salesflow.test?subject=Tư vấn {{ urlencode($product->name) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400"><flux:icon.arrow-right class="size-4" /> Trao đổi với Sales</a>
        </div>
    </section>
</div>
