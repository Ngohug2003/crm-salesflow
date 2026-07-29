@extends('layouts.app')

@section('content')
    <div>
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-slate-500">CRM / Sản phẩm</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Sản phẩm</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                    Màn hình mẫu để kiểm tra đầy đủ Permission xem, tạo, sửa và xóa. Dữ liệu hiện chưa được lưu vào database.
                </p>
            </div>

            @can('products.create')
                <flux:button href="{{ route('products.create') }}" wire:navigate.hover variant="primary">
                    Thêm sản phẩm
                </flux:button>
            @endcan
        </div>

        @if (session('success'))
            <div role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <section class="crm-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Mã sản phẩm</th>
                            <th class="px-4 py-3 font-medium">Tên sản phẩm</th>
                            <th class="px-4 py-3 font-medium">Phân loại</th>
                            <th class="px-4 py-3 font-medium">Đơn giá</th>
                            <th class="px-4 py-3 font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($products as $product)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-900/50">
                                <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">{{ $product['code'] }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('products.show', $product['id']) }}" wire:navigate.hover class="font-medium text-slate-950 hover:text-blue-600 dark:text-white dark:hover:text-blue-400">
                                        {{ $product['name'] }}
                                    </a>
                                    <p class="mt-1 text-xs text-slate-500">{{ $product['unit'] }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $product['category'] }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ number_format($product['price'], 0, ',', '.') }} ₫</td>
                                <td class="px-4 py-3">
                                    <flux:badge color="green" size="sm">{{ $product['status'] }}</flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <flux:button href="{{ route('products.show', $product['id']) }}" wire:navigate.hover size="sm" variant="ghost">
                                            Chi tiết
                                        </flux:button>
                                        @can('products.update')
                                            <flux:button href="{{ route('products.edit', $product['id']) }}" wire:navigate.hover size="sm" variant="ghost">
                                                Sửa
                                            </flux:button>
                                        @endcan
                                        @can('products.delete')
                                            <form method="POST" action="{{ route('products.destroy', $product['id']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <flux:button type="submit" size="sm" variant="danger">
                                                    Xóa
                                                </flux:button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
