<div class="space-y-6">
    <div class="data-list-heading">
        <div>
            <p class="text-sm text-slate-500">Danh mục nội thất</p>
            <h1 class="text-xl font-semibold">{{ $productId ? 'Chỉnh sửa mẫu sản phẩm' : 'Thêm mẫu sản phẩm' }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Khai báo thông tin chung. Kích thước, vật liệu và giá bán cụ thể được quản lý bằng biến thể.
            </p>
        </div>
        <flux:button href="{{ route('products.index') }}" wire:navigate variant="ghost">Quay lại</flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="crm-card space-y-5">
            <div>
                <h2 class="font-semibold">Thông tin nhận diện</h2>
                <p class="mt-1 text-sm text-slate-500">Mỗi mã đại diện cho một dòng hoặc mẫu nội thất.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="sku" label="Mã sản phẩm *" placeholder="VD: BAN-ATLAS" required />
                <flux:input wire:model="name" label="Tên mẫu sản phẩm *" placeholder="VD: Bàn làm việc Atlas" required />

                <x-forms.smart-select wire:model="categoryId" label="Danh mục *">
                    <option value="">Chọn danh mục</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </x-forms.smart-select>

                <x-forms.smart-select wire:model="brandId" label="Hãng / nhà sản xuất">
                    <option value="">Chưa xác định</option>
                    @foreach ($brands as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </x-forms.smart-select>

                <flux:input wire:model="model" label="Model / bộ sưu tập" />
                <flux:input wire:model="usageArea" label="Không gian sử dụng" placeholder="Phòng làm việc, phòng họp..." />
            </div>

            <flux:textarea wire:model="description" label="Mô tả sản phẩm" rows="4" />
        </section>

        <section class="crm-card space-y-5">
            <div>
                <h2 class="font-semibold">Thông tin thương mại mặc định</h2>
                <p class="mt-1 text-sm text-slate-500">Dùng làm giá tham khảo khi tạo biến thể đầu tiên.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <flux:input wire:model="unit" label="Đơn vị tính *" placeholder="Cái, Bộ..." required />
                <x-forms.money-input model="standardPrice" label="Giá tham khảo *" required />
                <flux:input wire:model="vatPercent" type="number" min="0" max="100" step="0.01" label="VAT (%) *" />
                <flux:input wire:model="warrantyMonths" type="number" min="0" max="120" label="Bảo hành mặc định (tháng)" />

                <x-forms.smart-select wire:model="commercialStatus" label="Trạng thái thương mại">
                    <option value="active">Đang kinh doanh</option>
                    <option value="made_to_order">Sản xuất theo đơn</option>
                    <option value="discontinued">Ngừng kinh doanh</option>
                </x-forms.smart-select>

                <div class="flex items-end pb-2">
                    <flux:checkbox wire:model="isActive" label="Cho phép sử dụng trong bán hàng" />
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-2">
            <flux:button href="{{ route('products.index') }}" wire:navigate variant="ghost">Hủy</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Lưu mẫu sản phẩm</flux:button>
        </div>
    </form>
</div>
