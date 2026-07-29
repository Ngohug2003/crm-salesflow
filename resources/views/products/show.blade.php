@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-slate-500">CRM / Sản phẩm / {{ $product['code'] }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ $product['name'] }}</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Trang chi tiết mẫu, chưa liên kết database.</p>
            </div>
            <div class="flex gap-2">
                <flux:button href="{{ route('products.index') }}" wire:navigate.hover variant="ghost">Quay lại</flux:button>
                @can('products.update')
                    <flux:button href="{{ route('products.edit', $product['id']) }}" wire:navigate.hover variant="primary">Chỉnh sửa</flux:button>
                @endcan
            </div>
        </div>

        @if (session('success'))
            <div role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <section class="crm-card">
            <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Mã sản phẩm</dt>
                    <dd class="mt-2 font-mono text-sm text-slate-950 dark:text-white">{{ $product['code'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Phân loại</dt>
                    <dd class="mt-2 text-sm text-slate-950 dark:text-white">{{ $product['category'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Đơn vị tính</dt>
                    <dd class="mt-2 text-sm text-slate-950 dark:text-white">{{ $product['unit'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Đơn giá</dt>
                    <dd class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ number_format($product['price'], 0, ',', '.') }} ₫</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Trạng thái</dt>
                    <dd class="mt-2"><flux:badge color="green">{{ $product['status'] }}</flux:badge></dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
