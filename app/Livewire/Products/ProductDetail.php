<?php

declare(strict_types=1);

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductSupplier;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\FurnitureCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
final class ProductDetail extends Component
{
    use WithFileUploads;

    public int $productId;

    public bool $showVariantEditor = false;

    public ?int $variantId = null;

    public string $variantSku = '';

    public string $variantName = '';

    public string $lengthMm = '';

    public string $widthMm = '';

    public string $heightMm = '';

    public string $material = '';

    public string $color = '';

    public string $finish = '';

    public string $variantUnit = 'Cái';

    public string $variantPrice = '0';

    public string $variantVatPercent = '10';

    public string $variantWarrantyMonths = '12';

    public string $variantLeadTimeDays = '0';

    public string $variantStatus = 'active';

    public bool $variantIsDefault = false;

    public bool $variantIsActive = true;

    public bool $showSupplierOfferEditor = false;

    public string $offerVariantId = '';

    public string $offerSupplierId = '';

    public string $supplierSku = '';

    public string $purchasePrice = '';

    public string $supplierLeadTimeDays = '0';

    public bool $isPreferredSupplier = false;

    public bool $showMediaUploader = false;

    /** @var list<TemporaryUploadedFile> */
    public array $mediaUploads = [];

    public string $mediaVariantId = '';

    public bool $makeFirstMediaPrimary = false;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    public function createVariant(): void
    {
        $product = $this->product();
        $this->authorize('update', $product);
        $this->resetVariantForm();
        $this->variantSku = $product->sku.'-';
        $this->variantUnit = $product->unit;
        $this->variantPrice = (string) $product->standard_price;
        $this->variantVatPercent = (string) $product->vat_percent;
        $this->variantWarrantyMonths = (string) $product->warranty_months;
        $this->variantIsDefault = ! $product->variants()->exists();
        $this->showVariantEditor = true;
    }

    public function editVariant(int $variantId): void
    {
        $product = $this->product();
        $this->authorize('update', $product);
        $variant = $this->variant($product, $variantId);
        $this->variantId = $variant->id;
        $this->variantSku = $variant->sku;
        $this->variantName = (string) $variant->name;
        $this->lengthMm = (string) $variant->length_mm;
        $this->widthMm = (string) $variant->width_mm;
        $this->heightMm = (string) $variant->height_mm;
        $this->material = (string) $variant->material;
        $this->color = (string) $variant->color;
        $this->finish = (string) $variant->finish;
        $this->variantUnit = $variant->unit;
        $this->variantPrice = (string) $variant->standard_price;
        $this->variantVatPercent = (string) $variant->vat_percent;
        $this->variantWarrantyMonths = (string) $variant->warranty_months;
        $this->variantLeadTimeDays = (string) $variant->lead_time_days;
        $this->variantStatus = $variant->commercial_status;
        $this->variantIsDefault = $variant->is_default;
        $this->variantIsActive = $variant->is_active;
        $this->showVariantEditor = true;
    }

    public function saveVariant(FurnitureCatalogService $service): void
    {
        $validated = $this->validate([
            'variantSku' => ['required', 'string', 'max:100'],
            'variantName' => ['nullable', 'string', 'max:255'],
            'lengthMm' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'widthMm' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'heightMm' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'material' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:120'],
            'finish' => ['nullable', 'string', 'max:255'],
            'variantUnit' => ['required', 'string', 'max:50'],
            'variantPrice' => ['required', 'numeric', 'min:0'],
            'variantVatPercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'variantWarrantyMonths' => ['required', 'integer', 'min:0', 'max:120'],
            'variantLeadTimeDays' => ['required', 'integer', 'min:0', 'max:3650'],
            'variantStatus' => ['required', 'in:active,made_to_order,discontinued'],
            'variantIsDefault' => ['boolean'],
            'variantIsActive' => ['boolean'],
        ]);

        $product = $this->product();
        /** @var User $actor */
        $actor = Auth::user();
        $variant = $this->variantId === null ? null : $this->variant($product, $this->variantId);
        $service->saveVariant($actor, $product, $variant, [
            'sku' => strtoupper(trim($validated['variantSku'])),
            'name' => trim($validated['variantName']) ?: null,
            'length_mm' => $validated['lengthMm'] !== '' ? (int) $validated['lengthMm'] : null,
            'width_mm' => $validated['widthMm'] !== '' ? (int) $validated['widthMm'] : null,
            'height_mm' => $validated['heightMm'] !== '' ? (int) $validated['heightMm'] : null,
            'material' => trim($validated['material']) ?: null,
            'color' => trim($validated['color']) ?: null,
            'finish' => trim($validated['finish']) ?: null,
            'unit' => trim($validated['variantUnit']),
            'standard_price' => $validated['variantPrice'],
            'vat_percent' => $validated['variantVatPercent'],
            'warranty_months' => (int) $validated['variantWarrantyMonths'],
            'lead_time_days' => (int) $validated['variantLeadTimeDays'],
            'commercial_status' => $validated['variantStatus'],
            'is_default' => $validated['variantIsDefault'],
            'is_active' => $validated['variantIsActive'],
        ]);

        $this->showVariantEditor = false;
        session()->flash('success', 'Đã lưu biến thể sản phẩm.');
    }

    public function createSupplierOffer(int $variantId): void
    {
        $product = $this->product();
        $this->authorize('update', $product);
        $variant = $this->variant($product, $variantId);
        $this->resetOfferForm();
        $this->offerVariantId = (string) $variant->id;
        $this->supplierLeadTimeDays = (string) $variant->lead_time_days;
        $this->showSupplierOfferEditor = true;
    }

    public function editSupplierOffer(int $variantId, int $supplierId): void
    {
        $product = $this->product();
        $this->authorize('update', $product);
        $variant = $this->variant($product, $variantId);
        $supplier = ProductSupplier::query()->findOrFail($supplierId);
        $offer = DB::table('product_supplier_variant')
            ->where('product_variant_id', $variant->id)
            ->where('product_supplier_id', $supplier->id)
            ->first();
        abort_if($offer === null, 404);

        $this->offerVariantId = (string) $variant->id;
        $this->offerSupplierId = (string) $supplier->id;
        $this->supplierSku = (string) $offer->supplier_sku;
        $this->purchasePrice = (string) $offer->purchase_price;
        $this->supplierLeadTimeDays = (string) $offer->lead_time_days;
        $this->isPreferredSupplier = (bool) $offer->is_preferred;
        $this->showSupplierOfferEditor = true;
    }

    public function saveSupplierOffer(FurnitureCatalogService $service): void
    {
        $validated = $this->validate([
            'offerVariantId' => ['required', 'integer'],
            'offerSupplierId' => ['required', 'integer', 'exists:product_suppliers,id'],
            'supplierSku' => ['nullable', 'string', 'max:100'],
            'purchasePrice' => ['nullable', 'numeric', 'min:0'],
            'supplierLeadTimeDays' => ['required', 'integer', 'min:0', 'max:3650'],
            'isPreferredSupplier' => ['boolean'],
        ]);

        $product = $this->product();
        $variant = $this->variant($product, (int) $validated['offerVariantId']);
        $supplier = ProductSupplier::query()->findOrFail((int) $validated['offerSupplierId']);
        /** @var User $actor */
        $actor = Auth::user();
        $service->saveSupplierOffer($actor, $product, $variant, $supplier, [
            'supplier_sku' => trim($validated['supplierSku']) ?: null,
            'purchase_price' => $validated['purchasePrice'] !== '' ? $validated['purchasePrice'] : null,
            'lead_time_days' => (int) $validated['supplierLeadTimeDays'],
            'is_preferred' => $validated['isPreferredSupplier'],
        ]);

        $this->showSupplierOfferEditor = false;
        session()->flash('success', 'Đã cập nhật nguồn cung cho biến thể.');
    }

    public function openMediaUploader(): void
    {
        $product = $this->product();
        $this->authorize('update', $product);
        $this->reset(['mediaUploads', 'mediaVariantId']);
        $this->makeFirstMediaPrimary = ! $product->media()->where('is_primary', true)->exists();
        $this->resetValidation();
        $this->showMediaUploader = true;
    }

    public function uploadMedia(FurnitureCatalogService $service): void
    {
        $validated = $this->validate([
            'mediaUploads' => ['required', 'array', 'min:1', 'max:8'],
            'mediaUploads.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'mediaVariantId' => ['nullable', 'integer'],
            'makeFirstMediaPrimary' => ['boolean'],
        ], [
            'mediaUploads.required' => 'Vui lòng chọn ít nhất một ảnh.',
            'mediaUploads.max' => 'Mỗi lần chỉ được tải tối đa 8 ảnh.',
            'mediaUploads.*.image' => 'Tệp tải lên phải là hình ảnh hợp lệ.',
            'mediaUploads.*.mimes' => 'Ảnh chỉ hỗ trợ JPG, PNG hoặc WebP.',
            'mediaUploads.*.max' => 'Mỗi ảnh không được vượt quá 5 MB.',
        ]);

        $product = $this->product();
        $variant = $validated['mediaVariantId'] === ''
            ? null
            : $this->variant($product, (int) $validated['mediaVariantId']);
        /** @var User $actor */
        $actor = Auth::user();
        $service->uploadMedia($actor, $product, $this->mediaUploads, $variant, $this->makeFirstMediaPrimary);

        $this->showMediaUploader = false;
        $this->reset(['mediaUploads', 'mediaVariantId']);
        session()->flash('success', 'Đã thêm ảnh vào thư viện sản phẩm.');
    }

    public function setPrimaryMedia(int $mediaId, FurnitureCatalogService $service): void
    {
        $product = $this->product();
        /** @var User $actor */
        $actor = Auth::user();
        $service->setPrimaryMedia($actor, $product, $this->media($product, $mediaId));
        session()->flash('success', 'Đã thay đổi ảnh chính sản phẩm.');
    }

    public function moveMedia(int $mediaId, string $direction, FurnitureCatalogService $service): void
    {
        $product = $this->product();
        /** @var User $actor */
        $actor = Auth::user();
        $service->moveMedia($actor, $product, $this->media($product, $mediaId), $direction);
    }

    public function deleteMedia(int $mediaId, FurnitureCatalogService $service): void
    {
        $product = $this->product();
        /** @var User $actor */
        $actor = Auth::user();
        $service->deleteMedia($actor, $product, $this->media($product, $mediaId));
        session()->flash('success', 'Đã xóa ảnh khỏi thư viện sản phẩm.');
    }

    public function render(): View
    {
        $product = $this->product()
            ->load([
                'category',
                'brand',
                'media.variant',
                'variants' => fn ($query) => $query->with('suppliers')->orderByDesc('is_default')->orderBy('sku'),
            ]);
        $this->authorize('view', $product);

        return view('livewire.products.product-detail', [
            'product' => $product,
            'suppliers' => ProductSupplier::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function product(): Product
    {
        return Product::query()->findOrFail($this->productId);
    }

    private function variant(Product $product, int $variantId): ProductVariant
    {
        return ProductVariant::query()
            ->where('product_id', $product->id)
            ->findOrFail($variantId);
    }

    private function media(Product $product, int $mediaId): ProductMedia
    {
        return ProductMedia::query()
            ->where('product_id', $product->id)
            ->findOrFail($mediaId);
    }

    private function resetVariantForm(): void
    {
        $this->reset([
            'variantId', 'variantSku', 'variantName', 'lengthMm', 'widthMm', 'heightMm',
            'material', 'color', 'finish', 'variantPrice', 'variantLeadTimeDays',
        ]);
        $this->variantUnit = 'Cái';
        $this->variantVatPercent = '10';
        $this->variantWarrantyMonths = '12';
        $this->variantStatus = 'active';
        $this->variantIsDefault = false;
        $this->variantIsActive = true;
        $this->resetValidation();
    }

    private function resetOfferForm(): void
    {
        $this->reset(['offerVariantId', 'offerSupplierId', 'supplierSku', 'purchasePrice']);
        $this->supplierLeadTimeDays = '0';
        $this->isPreferredSupplier = false;
        $this->resetValidation();
    }
}
