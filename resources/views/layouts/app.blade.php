<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>if (localStorage.getItem('salesflow-theme') === 'dark' || (!localStorage.getItem('salesflow-theme') && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark')</script>
    <title>{{ $title ?? 'SalesFlow CRM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased dark:bg-slate-950 dark:text-slate-100"
      x-data="{ sidebar: localStorage.getItem('salesflow-sidebar') !== 'closed', mobileNav: false }">
    <div class="min-h-screen lg:grid" :class="sidebar ? 'lg:grid-cols-[17rem_1fr]' : 'lg:grid-cols-[5rem_1fr]'">
        <aside class="fixed inset-y-0 left-0 z-40 w-72 border-r border-slate-200 bg-white p-4 transition dark:border-slate-800 dark:bg-slate-900 lg:static lg:w-auto"
               :class="mobileNav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between gap-3 px-2 py-2">
                    <a href="{{ route('dashboard') }}" wire:navigate.hover class="flex min-w-0 items-center gap-3">
                        {{-- <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-400 font-black text-slate-950">SF</span> --}}
                        <span x-show="sidebar" class="truncate font-semibold">SalesFlow CRM</span>
                    </a>
                    <button class="lg:hidden" @click="mobileNav=false" aria-label="Đóng menu">✕</button>
                </div>
                <nav class="mt-8 space-y-1">
                    <a href="{{ route('dashboard') }}" wire:navigate.hover @class(['nav-link', 'nav-link-active' => request()->routeIs('dashboard')])><span>⌂</span><span x-show="sidebar">Dashboard</span></a>
                    @can('viewAny', \App\Models\User::class)
                        <a href="{{ route('users.index') }}" wire:navigate.hover @class(['nav-link', 'nav-link-active' => request()->routeIs('users.*')])><span>♙</span><span x-show="sidebar">Người dùng</span></a>
                    @endcan
                    @can('viewAny', \App\Models\Department::class)
                        <a href="{{ route('departments.index') }}" wire:navigate.hover @class(['nav-link', 'nav-link-active' => request()->routeIs('departments.*')])><span>▤</span><span x-show="sidebar">Phòng ban</span></a>
                    @endcan
                    @foreach (['Leads' => '◎', 'Companies' => '▦', 'Contacts' => '♙', 'Opportunities' => '◇', 'Pipelines' => '◫', 'Activities' => '◷', 'Tasks' => '✓', 'Reports' => '⌁'] as $label => $icon)
                        <span class="nav-link cursor-not-allowed opacity-55" title="Có trong phase tiếp theo"><span>{{ $icon }}</span><span x-show="sidebar">{{ $label }}</span></span>
                    @endforeach
                    <a href="{{ route('help.roles') }}" wire:navigate.hover @class(['nav-link', 'nav-link-active' => request()->routeIs('help.roles')])><span>?</span><span x-show="sidebar">Vai trò & quyền</span></a>
                </nav>
                <div class="mt-auto border-t border-slate-200 pt-4 dark:border-slate-800">
                    <div class="flex items-center gap-3 px-3 py-2">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-200 text-sm font-semibold dark:bg-slate-700">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        <div x-show="sidebar" class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
        <div class="min-w-0">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sm:px-6">
                <flux:button variant="ghost" square class="hidden lg:inline-flex" @click="sidebar=!sidebar; localStorage.setItem('salesflow-sidebar', sidebar ? 'open' : 'closed')" aria-label="Thu gọn sidebar">☰</flux:button>
                <flux:button variant="ghost" square class="lg:hidden" @click="mobileNav=true" aria-label="Mở menu">☰</flux:button>
                <div class="relative hidden max-w-lg flex-1 md:block">
                    <flux:input disabled placeholder="Tìm kiếm toàn hệ thống — sắp có" icon="magnifying-glass" />
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <flux:button variant="ghost" square x-on:click="window.salesflow.toggleTheme()" aria-label="Đổi giao diện">◐</flux:button>
                    <form method="POST" action="{{ route('logout') }}">@csrf<flux:button type="submit" variant="ghost">Đăng xuất</flux:button></form>
                </div>
            </header>
            <main class="p-4 sm:p-6 lg:p-8">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
        </div>
    </div>
    <div x-show="mobileNav" x-transition.opacity @click="mobileNav=false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
    @fluxScripts
</body>
</html>
