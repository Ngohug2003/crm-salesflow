<!DOCTYPE html>
<html lang="vi" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Lỗi hệ thống — SalesFlow CRM</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="grid min-h-full place-items-center bg-slate-50 p-6 text-slate-950 dark:bg-slate-950 dark:text-white">
        <main class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="mx-auto grid size-12 place-items-center rounded-xl bg-rose-100 font-bold text-rose-700 dark:bg-rose-400/10 dark:text-rose-300">500</span>
            <h1 class="mt-5 text-2xl font-semibold">Hệ thống đang gặp sự cố</h1>
            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Vui lòng thử lại sau. Nếu lỗi tiếp tục xảy ra, hãy gửi mã tra cứu bên dưới cho quản trị viên.</p>
            @if (request()->attributes->get('request_id'))
                <div class="mt-6 rounded-xl bg-slate-100 px-4 py-3 text-sm dark:bg-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Mã tra cứu:</span>
                    <code class="ml-1 break-all font-semibold">{{ request()->attributes->get('request_id') }}</code>
                </div>
            @endif
            <a href="{{ url('/') }}" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white hover:bg-emerald-500">Về trang chính</a>
        </main>
    </body>
</html>
