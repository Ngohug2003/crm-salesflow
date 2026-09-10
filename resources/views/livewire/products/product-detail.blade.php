<div class="space-y-6">
    <div class="data-list-heading">
        <div>
            <p class="text-sm text-slate-500">
                {{ $product->category?->name ?: 'Chưa phân loại' }}
                @if ($product->brand)
                    · {{ $product->brand->name }}
                @endif
            </p>
            <h1 class="text-xl font-semibold">{{ $product->name }}</h1>
            <p class="mt-1 font-mono text-sm text-slate-500">
                {{ $product->sku }}{{ $product->model ? ' · '.$product->model : '' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button href="{{ route('products.index') }}" wire:navigate variant="ghost">Danh sách</flux:button>
            @can('update', $product)
                <flux:button href="{{ route('products.edit', $product) }}" wire:navigate variant="ghost">Sửa thông tin</flux:button>
                <flux:button wire:click="createVariant" variant="primary" icon="plus">Thêm biến thể</flux:button>
            @endcan
        </div>
    </div>

    <section class="crm-card">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold">Hình ảnh sản phẩm</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Ảnh chính dùng ở danh sách catalog; thư viện lưu các góc chụp và cấu hình thực tế.
                </p>
            </div>
            @can('update', $product)
                <flux:button wire:click="openMediaUploader" size="sm" variant="primary" icon="photo">
                    Thêm ảnh
                </flux:button>
            @endcan
        </div>

        @if ($product->media->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 px-6 py-10 text-center dark:border-slate-700">
                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800">
                    <flux:icon.photo class="size-5" />
                </div>
                <p class="mt-3 font-medium">Chưa có hình ảnh sản phẩm</p>
                <p class="mt-1 text-sm text-slate-500">Tải ảnh tổng thể, góc chi tiết hoặc ảnh theo từng biến thể.</p>
                @can('update', $product)
                    <flux:button wire:click="openMediaUploader" class="mt-4" size="sm" variant="ghost">
                        Tải ảnh đầu tiên
                    </flux:button>
                @endcan
            </div>
        @else
            @php
                $primaryMedia = $product->media->firstWhere('is_primary', true) ?? $product->media->first();
            @endphp
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,1fr)]">
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
                    <img
                        src="{{ route('products.media.show', [$product->id, $primaryMedia->id]) }}"
                        alt="Ảnh chính của {{ $product->name }}"
                        class="aspect-[4/3] w-full object-contain"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-2">
                    @foreach ($product->media as $media)
                        <article
                            wire:key="product-media-{{ $media->id }}"
                            class="group overflow-hidden rounded-xl border {{ $media->is_primary ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-slate-200 dark:border-slate-700' }} bg-white dark:bg-slate-900"
                        >
                            <div class="relative">
                                <img
                                    src="{{ route('products.media.show', [$product->id, $media->id]) }}"
                                    alt="{{ $media->original_name }}"
                                    loading="lazy"
                                    class="aspect-[4/3] w-full object-cover"
                                />
                                @if ($media->is_primary)
                                    <span class="absolute left-2 top-2 rounded-md bg-emerald-600 px-2 py-1 text-[11px] font-medium text-white shadow-sm">
                                        Ảnh chính
                                    </span>
                                @endif
                            </div>
                            <div class="space-y-2 p-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-medium" title="{{ $media->original_name }}">{{ $media->original_name }}</p>
                                    <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                        {{ $media->variant?->sku ?: 'Ảnh chung' }} · {{ number_format($media->size / 1024, 0, ',', '.') }} KB
                                    </p>
                                </div>
                                @can('update', $product)
                                    <div class="flex items-center justify-between border-t border-slate-100 pt-2 dark:border-slate-800">
                                        <div class="flex gap-0.5">
                                            <flux:button wire:click="moveMedia({{ $media->id }}, 'up')" size="xs" variant="ghost" icon="chevron-left" aria-label="Chuyển ảnh sang trái" />
                                            <flux:button wire:click="moveMedia({{ $media->id }}, 'down')" size="xs" variant="ghost" icon="chevron-right" aria-label="Chuyển ảnh sang phải" />
                                        </div>
                                        <div class="flex gap-0.5">
                                            @unless ($media->is_primary)
                                                <flux:button wire:click="setPrimaryMedia({{ $media->id }})" size="xs" variant="ghost" icon="star" aria-label="Đặt làm ảnh chính" />
                                            @endunless
                                            <flux:button
                                                wire:click="deleteMedia({{ $media->id }})"
                                                wire:confirm="Xóa ảnh này khỏi thư viện sản phẩm?"
                                                size="xs"
                                                variant="ghost"
                                                icon="trash"
                                                aria-label="Xóa ảnh"
                                            />
                                        </div>
                                    </div>
                                @endcan
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="crm-card lg:col-span-2">
            <h2 class="font-semibold">Thông tin mẫu sản phẩm</h2>
            <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-slate-500">Mô tả</dt>
                    <dd class="mt-1 text-sm">{{ $product->description ?: 'Chưa có mô tả.' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Không gian sử dụng</dt>
                    <dd class="mt-1 text-sm">{{ $product->specifications['usage_area'] ?? 'Chưa xác định' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Đơn vị mặc định</dt>
                    <dd class="mt-1 font-medium">{{ $product->unit }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Bảo hành mặc định</dt>
                    <dd class="mt-1 font-medium">{{ $product->warranty_months }} tháng</dd>
                </div>
            </dl>
        </section>

        <aside class="crm-card space-y-4">
            <div>
                <p class="text-xs text-slate-500">Giá tham khảo</p>
                <p class="mt-1 text-xl font-semibold">{{ number_format((float) $product->standard_price, 0, ',', '.') }} ₫</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Thuế VAT</p>
                <p class="mt-1 font-medium">{{ number_format((float) $product->vat_percent, 2, ',', '.') }}%</p>
            </div>
            @php
                $productStatus = [
                    'active' => ['Đang kinh doanh', 'emerald'],
                    'made_to_order' => ['Sản xuất theo đơn', 'amber'],
                    'discontinued' => ['Ngừng kinh doanh', 'zinc'],
                ][$product->commercial_status] ?? [$product->commercial_status, 'zinc'];
            @endphp
            <flux:badge :color="$productStatus[1]">{{ $productStatus[0] }}</flux:badge>
        </aside>
    </div>

    <section class="crm-card">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold">Biến thể bán hàng</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Mỗi biến thể có SKU, kích thước, vật liệu, giá và nguồn cung riêng.
                </p>
            </div>
            @can('update', $product)
                <flux:button wire:click="createVariant" size="sm" variant="primary" icon="plus">Thêm biến thể</flux:button>
            @endcan
        </div>

        <div class="space-y-4">
            @forelse ($product->variants as $variant)
                <article class="rounded-xl border border-slate-200 p-4 dark:border-slate-700" wire:key="variant-{{ $variant->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold">{{ $variant->name ?: $variant->sku }}</h3>
                                @if ($variant->is_default)
                                    <flux:badge color="blue">Mặc định</flux:badge>
                                @endif
                                <flux:badge :color="$variant->is_active ? 'emerald' : 'zinc'">
                                    {{ $variant->is_active ? 'Đang dùng' : 'Tạm ngừng' }}
                                </flux:badge>
                            </div>
                            <p class="mt-1 font-mono text-xs text-slate-500">{{ $variant->sku }}</p>
                        </div>
                        @can('update', $product)
                            <div class="flex gap-1">
                                <flux:button wire:click="editVariant({{ $variant->id }})" size="sm" variant="ghost">Sửa</flux:button>
                                <flux:button wire:click="createSupplierOffer({{ $variant->id }})" size="sm" variant="ghost">
                                    Thêm nguồn cung
                                </flux:button>
                            </div>
                        @endcan
                    </div>

                    <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-xs text-slate-500">Kích thước D × R × C</dt>
                            <dd class="mt-1 text-sm">
                                @if ($variant->length_mm || $variant->width_mm || $variant->height_mm)
                                    {{ $variant->length_mm ?: '—' }} × {{ $variant->width_mm ?: '—' }} × {{ $variant->height_mm ?: '—' }} mm
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Vật liệu / hoàn thiện</dt>
                            <dd class="mt-1 text-sm">{{ $variant->material ?: '—' }} · {{ $variant->finish ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Màu sắc</dt>
                            <dd class="mt-1 text-sm">{{ $variant->color ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Giá bán chuẩn</dt>
                            <dd class="mt-1 font-semibold">{{ number_format((float) $variant->standard_price, 0, ',', '.') }} ₫</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Thời gian đáp ứng</dt>
                            <dd class="mt-1 text-sm">{{ $variant->lead_time_days }} ngày</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">VAT / bảo hành</dt>
                            <dd class="mt-1 text-sm">{{ $variant->vat_percent }}% · {{ $variant->warranty_months }} tháng</dd>
                        </div>
                    </dl>

                    <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                        <h4 class="text-sm font-medium">Nguồn cung</h4>
                        @if ($variant->suppliers->isEmpty())
                            <p class="mt-2 text-sm text-slate-500">Chưa gán nhà cung cấp cho biến thể này.</p>
                        @else
                            <div class="mt-2 overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="text-xs text-slate-500">
                                        <tr>
                                            <th class="pb-2 font-medium">Nhà cung cấp</th>
                                            <th class="pb-2 font-medium">Mã NCC</th>
                                            <th class="pb-2 text-right font-medium">Giá mua</th>
                                            <th class="pb-2 text-right font-medium">Đáp ứng</th>
                                            <th class="pb-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($variant->suppliers as $supplier)
                                            <tr class="border-t border-slate-100 dark:border-slate-800">
                                                <td class="py-2">
                                                    {{ $supplier->name }}
                                                    @if ($supplier->pivot->is_preferred)
                                                        <flux:badge size="sm" color="blue">Ưu tiên</flux:badge>
                                                    @endif
                                                </td>
                                                <td class="py-2 font-mono text-xs">{{ $supplier->pivot->supplier_sku ?: '—' }}</td>
                                                <td class="py-2 text-right">
                                                    {{ $supplier->pivot->purchase_price !== null
                                                        ? number_format((float) $supplier->pivot->purchase_price, 0, ',', '.').' ₫'
                                                        : '—' }}
                                                </td>
                                                <td class="py-2 text-right">{{ $supplier->pivot->lead_time_days }} ngày</td>
                                                <td class="py-2 text-right">
                                                    @can('update', $product)
                                                        <flux:button
                                                            wire:click="editSupplierOffer({{ $variant->id }}, {{ $supplier->id }})"
                                                            size="sm"
                                                            variant="ghost"
                                                        >
                                                            Sửa
                                                        </flux:button>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 px-6 py-10 text-center dark:border-slate-700">
                    <p class="font-medium">Chưa có biến thể bán hàng</p>
                    <p class="mt-1 text-sm text-slate-500">Tạo cấu hình đầu tiên để xác định kích thước, vật liệu và giá bán.</p>
                    @can('update', $product)
                        <flux:button wire:click="createVariant" class="mt-4" variant="primary" icon="plus">
                            Tạo biến thể đầu tiên
                        </flux:button>
                    @endcan
                </div>
            @endforelse
        </div>
    </section>

    <flux:modal wire:model="showMediaUploader" class="w-full max-w-2xl">
        <form wire:submit="uploadMedia" class="space-y-5">
            <div>
                <flux:heading>Thêm ảnh sản phẩm</flux:heading>
                <flux:text class="mt-1">Chọn tối đa 8 ảnh JPG, PNG hoặc WebP; mỗi ảnh không quá 5 MB.</flux:text>
            </div>

            <flux:input
                wire:model="mediaUploads"
                type="file"
                label="Tệp hình ảnh *"
                accept="image/jpeg,image/png,image/webp"
                multiple
            />

            <div wire:loading wire:target="mediaUploads" class="text-sm text-slate-500">
                Đang chuẩn bị ảnh xem trước…
            </div>

            @if ($mediaUploads !== [])
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($mediaUploads as $upload)
                        <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
                            @if (str_starts_with((string) $upload->getMimeType(), 'image/'))
                                <img src="{{ $upload->temporaryUrl() }}" alt="Xem trước ảnh tải lên" class="aspect-square w-full object-cover" />
                            @endif
                            <p class="truncate px-2 py-1.5 text-[11px] text-slate-500" title="{{ $upload->getClientOriginalName() }}">
                                {{ $upload->getClientOriginalName() }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif

            <flux:field>
                <flux:label>Gắn với biến thể</flux:label>
                <x-forms.modal-searchable-select
                    wire:model="mediaVariantId"
                    placeholder="Ảnh chung của sản phẩm"
                    search-placeholder="Tìm SKU hoặc tên biến thể…"
                >
                    <option value="">Ảnh chung của sản phẩm</option>
                    @foreach ($product->variants as $variant)
                        <option value="{{ $variant->id }}">{{ $variant->sku }} — {{ $variant->name ?: $variant->color ?: 'Biến thể' }}</option>
                    @endforeach
                </x-forms.modal-searchable-select>
                <flux:error name="mediaVariantId" />
            </flux:field>

            <flux:checkbox wire:model="makeFirstMediaPrimary" label="Dùng ảnh đầu tiên làm ảnh chính" />

            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
                <flux:button type="button" variant="ghost" wire:click="$set('showMediaUploader', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="uploadMedia,mediaUploads">
                    Lưu vào thư viện
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showVariantEditor" class="w-full" style="width: 52rem; max-width: 95vw;">
        <form wire:submit="saveVariant" class="space-y-5">
            <div>
                <flux:heading>{{ $variantId ? 'Sửa biến thể' : 'Thêm biến thể' }}</flux:heading>
                <flux:text class="mt-1">Thông tin cấu hình thực tế mà Sale dùng để báo giá.</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <flux:input wire:model="variantSku" label="SKU biến thể *" required />
                <flux:input wire:model="variantName" label="Tên biến thể" placeholder="VD: 1400 × 700, màu óc chó" />
                <flux:input wire:model="variantUnit" label="Đơn vị tính *" required />
                <flux:input wire:model="lengthMm" type="number" min="1" label="Dài (mm)" />
                <flux:input wire:model="widthMm" type="number" min="1" label="Rộng (mm)" />
                <flux:input wire:model="heightMm" type="number" min="1" label="Cao (mm)" />
                <flux:input wire:model="material" label="Vật liệu" />
                <flux:input wire:model="color" label="Màu sắc" />
                <flux:input wire:model="finish" label="Hoàn thiện bề mặt" />
                <x-forms.money-input model="variantPrice" label="Giá bán chuẩn *" required />
                <flux:input wire:model="variantVatPercent" type="number" min="0" max="100" step="0.01" label="VAT (%)" />
                <flux:input wire:model="variantWarrantyMonths" type="number" min="0" label="Bảo hành (tháng)" />
                <flux:input wire:model="variantLeadTimeDays" type="number" min="0" label="Thời gian đáp ứng (ngày)" />
                <x-forms.smart-select wire:model="variantStatus" label="Trạng thái thương mại">
                    <option value="active">Đang kinh doanh</option>
                    <option value="made_to_order">Sản xuất theo đơn</option>
                    <option value="discontinued">Ngừng kinh doanh</option>
                </x-forms.smart-select>
            </div>

            <div class="flex flex-wrap gap-5">
                <flux:checkbox wire:model="variantIsDefault" label="Biến thể mặc định" />
                <flux:checkbox wire:model="variantIsActive" label="Cho phép sử dụng" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showVariantEditor', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary">Lưu biến thể</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showSupplierOfferEditor" class="w-full max-w-2xl">
        <form wire:submit="saveSupplierOffer" class="space-y-5">
            <div>
                <flux:heading>Nguồn cung biến thể</flux:heading>
                <flux:text class="mt-1">Ghi nhận giá mua và thời gian đáp ứng riêng của nhà cung cấp.</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-forms.smart-select wire:model="offerSupplierId" label="Nhà cung cấp *">
                    <option value="">Chọn nhà cung cấp</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->code }} — {{ $supplier->name }}</option>
                    @endforeach
                </x-forms.smart-select>
                <flux:input wire:model="supplierSku" label="Mã sản phẩm phía nhà cung cấp" />
                <x-forms.money-input model="purchasePrice" label="Giá mua dự kiến" />
                <flux:input wire:model="supplierLeadTimeDays" type="number" min="0" label="Thời gian đáp ứng (ngày)" />
            </div>

            <flux:checkbox wire:model="isPreferredSupplier" label="Đặt làm nhà cung cấp ưu tiên của biến thể" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showSupplierOfferEditor', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary">Lưu nguồn cung</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
