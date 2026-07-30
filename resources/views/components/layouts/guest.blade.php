<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>const appearance = localStorage.getItem('flux.appearance') || 'system'; if (appearance === 'dark' || (appearance === 'system' && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark')</script>
    <title>{{ $title ?? 'SalesFlow CRM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased dark:bg-slate-950 dark:text-slate-100">
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <a href="/" class="flex items-center gap-3 font-semibold">
                <span class="grid size-10 place-items-center rounded-xl bg-emerald-400 font-black text-slate-950">SF</span>
                SalesFlow CRM
            </a>
            <div class="max-w-xl">
                <p class="mb-5 text-sm font-semibold uppercase tracking-[.3em] text-emerald-300">Sell with clarity</p>
                <h1 class="text-5xl font-semibold leading-tight">Một workspace gọn gàng cho toàn bộ hành trình bán hàng.</h1>
                <p class="mt-6 text-lg leading-8 text-slate-300">Theo dõi lead, pipeline, công việc và doanh thu trong một trải nghiệm nhanh, rõ và nhất quán.</p>
            </div>
            <p class="text-sm text-slate-500">© {{ date('Y') }} SalesFlow CRM</p>
        </section>
        <section class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md">{{ $slot }}</div>
        </section>
    </main>
    @fluxScripts
</body>
</html>
