@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <p class="text-sm text-slate-500">CRM / Sản phẩm / {{ $product === null ? 'Thêm mới' : 'Chỉnh sửa' }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ $title }}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Form này dùng để kiểm tra Permission và validation. Dữ liệu gửi lên không được lưu vào database.
            </p>
        </div>

        <form method="POST" action="{{ $submitRoute }}" class="crm-card space-y-6">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input
                    name="code"
                    label="Mã sản phẩm"
                    value="{{ old('code', $product['code'] ?? '') }}"
                    placeholder="VD: SF-CRM-01"
                    required
                />
                <flux:input
                    name="name"
                    label="Tên sản phẩm"
                    value="{{ old('name', $product['name'] ?? '') }}"
                    required
                />
                <flux:input
                    name="category"
                    label="Phân loại"
                    value="{{ old('category', $product['category'] ?? '') }}"
                    placeholder="Phần mềm hoặc Dịch vụ"
                    required
                />
                <flux:input
                    name="unit"
                    label="Đơn vị tính"
                    value="{{ old('unit', $product['unit'] ?? '') }}"
                    required
                />
                <flux:input
                    name="price"
                    type="number"
                    min="0"
                    label="Đơn giá"
                    value="{{ old('price', $product['price'] ?? 0) }}"
                    required
                />
                <x-forms.smart-select name="status" label="Trạng thái">
                    <option value="active" @selected(old('status', 'active') === 'active')>Đang kinh doanh</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>Ngừng kinh doanh</option>
                </x-forms.smart-select>
            </div>

            @if ($errors->any())
                <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                    Vui lòng kiểm tra lại các trường bắt buộc.
                </div>
            @endif

            <div class="flex justify-end gap-2 border-t border-slate-200 pt-5 dark:border-slate-800">
                <flux:button href="{{ route('products.index') }}" wire:navigate.hover variant="ghost">Hủy</flux:button>
                <flux:button type="submit" variant="primary">{{ $product === null ? 'Tạo sản phẩm' : 'Lưu thay đổi' }}</flux:button>
            </div>
        </form>
    </div>
@endsection
