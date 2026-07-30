{{-- Top bar --}}
<flux:button variant="ghost" square class="hidden lg:inline-flex" @click="sidebar=!sidebar; localStorage.setItem('salesflow-sidebar', sidebar ? 'open' : 'closed')" aria-label="Thu gọn sidebar">
    <flux:icon.bars-3 class="size-5" />
</flux:button>
<flux:button variant="ghost" square class="lg:hidden" @click="mobileNav=true" aria-label="Mở menu">
    <flux:icon.bars-3 class="size-5" />
</flux:button>

{{-- Global search slot (disabled — backend chưa xây) --}}
<div class="relative hidden max-w-lg flex-1 md:block">
    <flux:input disabled placeholder="Tìm kiếm toàn hệ thống — sắp có" icon="magnifying-glass" />
</div>

<div class="ml-auto flex items-center gap-1">
    {{-- Quick create (disabled — hiển thị khi user có quyền tạo Lead) --}}
    @can('create', \App\Models\Lead::class)
        <flux:button variant="ghost" square disabled title="Tạo nhanh — sắp có" aria-label="Tạo nhanh">
            <flux:icon.plus-circle class="size-5" />
        </flux:button>
    @endcan

    {{-- Notification bell --}}
    <livewire:notification-menu />

    {{-- Dark mode toggle --}}
    <flux:button variant="ghost" square x-on:click="window.salesflow.toggleTheme()" aria-label="Đổi giao diện">
        <flux:icon.moon class="size-5 hidden dark:block" />
        <flux:icon.sun class="size-5 dark:hidden" />
    </flux:button>

    {{-- User dropdown menu --}}
    @include('layouts.partials._user-menu')
</div>
