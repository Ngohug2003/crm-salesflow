<div class="space-y-6">
    <div class="data-list-heading">
        <div>
            <p class="text-sm text-slate-500">Cấu hình danh mục nội thất</p>
            <h1 class="text-xl font-semibold">Danh mục & nhà cung cấp</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý dữ liệu dùng chung khi tạo mẫu, biến thể và nguồn cung.</p>
        </div>
        <flux:button href="{{ route('products.index') }}" wire:navigate variant="ghost">Quay lại sản phẩm</flux:button>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="crm-card">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Danh mục sản phẩm</h2>
                    <p class="mt-1 text-sm text-slate-500">Nhóm bàn, ghế, tủ, vách ngăn và dịch vụ.</p>
                </div>
                <flux:button wire:click="createCategory" size="sm" variant="primary" icon="plus">Thêm</flux:button>
            </div>

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Mã / tên</flux:table.column>
                        <flux:table.column>Danh mục cha</flux:table.column>
                        <flux:table.column>Trạng thái</flux:table.column>
                        <flux:table.column />
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($categories as $category)
                            <flux:table.row :key="$category->id">
                                <flux:table.cell variant="strong">
                                    {{ $category->name }}
                                    <div class="font-mono text-xs font-normal text-slate-500">{{ $category->code }}</div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $category->parent?->name ?: '—' }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$category->is_active ? 'emerald' : 'zinc'">
                                        {{ $category->is_active ? 'Đang dùng' : 'Tạm ngừng' }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button wire:click="editCategory({{ $category->id }})" size="sm" variant="ghost">Sửa</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </section>

        <section class="crm-card">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Nhà cung cấp</h2>
                    <p class="mt-1 text-sm text-slate-500">Đơn vị có thể cung ứng từng biến thể.</p>
                </div>
                <flux:button wire:click="createSupplier" size="sm" variant="primary" icon="plus">Thêm</flux:button>
            </div>

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Mã / tên</flux:table.column>
                        <flux:table.column>Liên hệ</flux:table.column>
                        <flux:table.column>Trạng thái</flux:table.column>
                        <flux:table.column />
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($suppliers as $supplier)
                            <flux:table.row :key="$supplier->id">
                                <flux:table.cell variant="strong">
                                    {{ $supplier->name }}
                                    <div class="font-mono text-xs font-normal text-slate-500">{{ $supplier->code }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $supplier->contact_name ?: '—' }}
                                    <div class="text-xs text-slate-500">{{ $supplier->phone ?: $supplier->email }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$supplier->is_active ? 'emerald' : 'zinc'">
                                        {{ $supplier->is_active ? 'Đang hợp tác' : 'Tạm ngừng' }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button wire:click="editSupplier({{ $supplier->id }})" size="sm" variant="ghost">Sửa</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </section>
    </div>

    <flux:modal wire:model="showCategoryEditor" class="w-full max-w-lg">
        <form wire:submit="saveCategory" class="space-y-4">
            <div>
                <flux:heading>{{ $categoryId ? 'Sửa danh mục' : 'Thêm danh mục' }}</flux:heading>
                <flux:text class="mt-1">Dữ liệu này được dùng để lọc và phân nhóm sản phẩm.</flux:text>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="categoryCode" label="Mã danh mục *" required />
                <flux:input wire:model="categoryName" label="Tên danh mục *" required />
                <flux:field>
                    <flux:label>Danh mục cha</flux:label>
                    <x-forms.modal-searchable-select
                        wire:model="categoryParentId"
                        placeholder="Không có danh mục cha"
                        search-placeholder="Tìm danh mục cha…"
                    >
                        <option value="">Không có danh mục cha</option>
                        @foreach ($categories as $category)
                            @if ($category->id !== $categoryId)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endif
                        @endforeach
                    </x-forms.modal-searchable-select>
                    <flux:error name="categoryParentId" />
                </flux:field>
                <flux:input wire:model="categorySortOrder" type="number" min="0" label="Thứ tự hiển thị" />
            </div>
            <flux:checkbox wire:model="categoryIsActive" label="Đang sử dụng" />
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showCategoryEditor', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary">Lưu danh mục</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showSupplierEditor" class="w-full max-w-2xl">
        <form wire:submit="saveSupplier" class="space-y-4">
            <div>
                <flux:heading>{{ $supplierId ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp' }}</flux:heading>
                <flux:text class="mt-1">Thông tin đầu mối và nhận diện pháp lý của nguồn cung.</flux:text>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="supplierCode" label="Mã nhà cung cấp *" required />
                <flux:input wire:model="supplierName" label="Tên nhà cung cấp *" required />
                <flux:input wire:model="supplierTaxCode" label="Mã số thuế" />
                <flux:input wire:model="supplierContactName" label="Người liên hệ" />
                <flux:input wire:model="supplierEmail" type="email" label="Email" />
                <flux:input wire:model="supplierPhone" label="Điện thoại" />
            </div>
            <flux:textarea wire:model="supplierAddress" label="Địa chỉ" rows="2" />
            <flux:checkbox wire:model="supplierIsActive" label="Đang hợp tác" />
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showSupplierEditor', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary">Lưu nhà cung cấp</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
