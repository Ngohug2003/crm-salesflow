<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-50 dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Public Quote — SalesFlow CRM' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="h-full font-sans antialiased text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-950">
    <div class="min-h-screen flex flex-col justify-between py-6 px-4 sm:px-6 lg:px-8">
        {{-- Guest Header Logo --}}
        <header class="max-w-4xl mx-auto w-full flex items-center justify-between py-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 font-bold text-white shadow-sm">
                    SF
                </div>
                <div>
                    <span class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">SalesFlow CRM</span>
                    <p class="text-xs text-slate-500">Cổng xác nhận báo giá công khai</p>
                </div>
            </div>
            <div class="text-xs text-slate-400">
                Bảo mật SSL 256-bit
            </div>
        </header>

        {{-- Main Guest Page Slot --}}
        <main class="max-w-4xl mx-auto w-full my-8">
            {{ $slot }}
        </main>

        {{-- Guest Footer --}}
        <footer class="max-w-4xl mx-auto w-full py-4 border-t border-slate-200 dark:border-slate-800 text-center text-xs text-slate-500">
            <p>© {{ date('Y') }} SalesFlow CRM Platform. Tất cả quyền được bảo lưu.</p>
        </footer>
    </div>

    @fluxScripts
</body>
</html>
