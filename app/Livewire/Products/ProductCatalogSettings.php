<?php

declare(strict_types=1);

namespace App\Livewire\Products;

use App\Models\ProductCategory;
use App\Models\ProductSupplier;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use App\Services\FurnitureCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class ProductCatalogSettings extends Component
{
    public bool $showCategoryEditor = false;

    public ?int $categoryId = null;

    public string $categoryCode = '';

    public string $categoryName = '';

    public string $categoryParentId = '';

    public string $categorySortOrder = '0';

    public bool $categoryIsActive = true;

    public bool $showSupplierEditor = false;

    public ?int $supplierId = null;

    public string $supplierCode = '';

    public string $supplierName = '';

    public string $supplierTaxCode = '';

    public string $supplierContactName = '';

    public string $supplierEmail = '';

    public string $supplierPhone = '';

    public string $supplierAddress = '';

    public bool $supplierIsActive = true;

    public function mount(DataScopeService $dataScope): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        abort_unless($dataScope->canWrite($actor), 403);
    }

    public function createCategory(): void
    {
        $this->resetCategoryForm();
        $this->showCategoryEditor = true;
    }

    public function editCategory(int $categoryId): void
    {
        $category = ProductCategory::query()->findOrFail($categoryId);
        $this->categoryId = $category->id;
        $this->categoryCode = $category->code;
        $this->categoryName = $category->name;
        $this->categoryParentId = (string) $category->parent_id;
        $this->categorySortOrder = (string) $category->sort_order;
        $this->categoryIsActive = $category->is_active;
        $this->showCategoryEditor = true;
    }

    public function saveCategory(FurnitureCatalogService $service): void
    {
        $validated = $this->validate([
            'categoryCode' => ['required', 'string', 'max:50'],
            'categoryName' => ['required', 'string', 'max:255'],
            'categoryParentId' => ['nullable', 'integer', 'exists:product_categories,id'],
            'categorySortOrder' => ['required', 'integer', 'min:0', 'max:9999'],
            'categoryIsActive' => ['boolean'],
        ]);
        $category = $this->categoryId === null ? null : ProductCategory::query()->findOrFail($this->categoryId);
        if ($category !== null && (int) $validated['categoryParentId'] === $category->id) {
            $this->addError('categoryParentId', 'Danh mục không thể là danh mục cha của chính nó.');

            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        $service->saveCategory($actor, $category, [
            'code' => strtoupper(trim($validated['categoryCode'])),
            'name' => trim($validated['categoryName']),
            'parent_id' => $validated['categoryParentId'] !== '' ? (int) $validated['categoryParentId'] : null,
            'sort_order' => (int) $validated['categorySortOrder'],
            'is_active' => $validated['categoryIsActive'],
        ]);
        $this->showCategoryEditor = false;
        session()->flash('success', 'Đã lưu danh mục nội thất.');
    }

    public function createSupplier(): void
    {
        $this->resetSupplierForm();
        $this->showSupplierEditor = true;
    }

    public function editSupplier(int $supplierId): void
    {
        $supplier = ProductSupplier::query()->findOrFail($supplierId);
        $this->supplierId = $supplier->id;
        $this->supplierCode = $supplier->code;
        $this->supplierName = $supplier->name;
        $this->supplierTaxCode = (string) $supplier->tax_code;
        $this->supplierContactName = (string) $supplier->contact_name;
        $this->supplierEmail = (string) $supplier->email;
        $this->supplierPhone = (string) $supplier->phone;
        $this->supplierAddress = (string) $supplier->address;
        $this->supplierIsActive = $supplier->is_active;
        $this->showSupplierEditor = true;
    }

    public function saveSupplier(FurnitureCatalogService $service): void
    {
        $validated = $this->validate([
            'supplierCode' => ['required', 'string', 'max:50'],
            'supplierName' => ['required', 'string', 'max:255'],
            'supplierTaxCode' => ['nullable', 'string', 'max:50'],
            'supplierContactName' => ['nullable', 'string', 'max:255'],
            'supplierEmail' => ['nullable', 'email', 'max:255'],
            'supplierPhone' => ['nullable', 'string', 'max:50'],
            'supplierAddress' => ['nullable', 'string', 'max:1000'],
            'supplierIsActive' => ['boolean'],
        ]);
        $supplier = $this->supplierId === null ? null : ProductSupplier::query()->findOrFail($this->supplierId);
        /** @var User $actor */
        $actor = Auth::user();
        $service->saveSupplier($actor, $supplier, [
            'code' => strtoupper(trim($validated['supplierCode'])),
            'name' => trim($validated['supplierName']),
            'tax_code' => trim($validated['supplierTaxCode']) ?: null,
            'contact_name' => trim($validated['supplierContactName']) ?: null,
            'email' => trim($validated['supplierEmail']) ?: null,
            'phone' => trim($validated['supplierPhone']) ?: null,
            'address' => trim($validated['supplierAddress']) ?: null,
            'is_active' => $validated['supplierIsActive'],
        ]);
        $this->showSupplierEditor = false;
        session()->flash('success', 'Đã lưu nhà cung cấp.');
    }

    public function render(): View
    {
        return view('livewire.products.product-catalog-settings', [
            'categories' => ProductCategory::query()->with('parent')->orderBy('sort_order')->orderBy('name')->get(),
            'suppliers' => ProductSupplier::query()->orderBy('name')->get(),
        ]);
    }

    private function resetCategoryForm(): void
    {
        $this->reset(['categoryId', 'categoryCode', 'categoryName', 'categoryParentId']);
        $this->categorySortOrder = '0';
        $this->categoryIsActive = true;
        $this->resetValidation();
    }

    private function resetSupplierForm(): void
    {
        $this->reset([
            'supplierId', 'supplierCode', 'supplierName', 'supplierTaxCode',
            'supplierContactName', 'supplierEmail', 'supplierPhone', 'supplierAddress',
        ]);
        $this->supplierIsActive = true;
        $this->resetValidation();
    }
}
