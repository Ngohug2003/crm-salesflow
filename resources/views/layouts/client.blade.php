<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-50 dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Danh mục nội thất — SalesFlow' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @stack('head')
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('client.products.index') }}" wire:navigate class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-sm font-bold text-white shadow-sm">SF</span>
                <span>
                    <span class="block text-sm font-bold tracking-tight">SalesFlow Furniture</span>
                    <span class="hidden text-[11px] text-slate-500 sm:block">Giải pháp nội thất văn phòng B2B</span>
                </span>
            </a>
            <nav class="flex items-center gap-1 text-sm">
                <a href="{{ route('client.products.index') }}" wire:navigate class="rounded-lg px-3 py-2 font-medium text-slate-700 transition hover:bg-slate-100 hover:text-emerald-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-emerald-400">Sản phẩm</a>
                <a href="mailto:sales@salesflow.test" class="hidden rounded-lg px-3 py-2 text-slate-500 transition hover:bg-slate-100 hover:text-emerald-700 sm:block dark:hover:bg-slate-800 dark:hover:text-emerald-400">Liên hệ tư vấn</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto min-h-[calc(100vh-9rem)] max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <span>SalesFlow Furniture · Nội thất văn phòng theo nhu cầu dự án</span>
            <span>© {{ date('Y') }} SalesFlow CRM</span>
        </div>
    </footer>
    @fluxScripts
</body>
</html>
