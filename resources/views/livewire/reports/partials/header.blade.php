@props(['title', 'description'])

<div class="space-y-4">
    <div>
        <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Dashboard và báo cáo</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ $title }}</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    </div>

    <nav class="flex flex-wrap gap-2" aria-label="Điều hướng báo cáo">
        <flux:button href="{{ route('dashboard') }}" wire:navigate size="sm" :variant="request()->routeIs('dashboard') ? 'primary' : 'subtle'">
            Tổng quan
        </flux:button>
        <flux:button href="{{ route('reports.funnel') }}" wire:navigate size="sm" :variant="request()->routeIs('reports.funnel') ? 'primary' : 'subtle'">
            Phễu chuyển đổi
        </flux:button>
        <flux:button href="{{ route('reports.revenue') }}" wire:navigate size="sm" :variant="request()->routeIs('reports.revenue') ? 'primary' : 'subtle'">
            Doanh thu
        </flux:button>
        <flux:button href="{{ route('reports.performance') }}" wire:navigate size="sm" :variant="request()->routeIs('reports.performance') ? 'primary' : 'subtle'">
            Hiệu suất
        </flux:button>
    </nav>
</div>
