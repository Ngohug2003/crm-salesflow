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
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased dark:bg-slate-950 dark:text-slate-100"
      x-data="{ sidebar: localStorage.getItem('salesflow-sidebar') !== 'closed', mobileNav: false }">
    <div class="min-h-screen lg:grid" :class="sidebar ? 'lg:grid-cols-[17rem_1fr]' : 'lg:grid-cols-[5rem_1fr]'">
        {{-- Sidebar --}}
        @persist('app-sidebar')
        <aside class="fixed inset-y-0 left-0 z-40 w-72 border-r border-slate-200 bg-white p-4 transition dark:border-slate-800 dark:bg-slate-900 lg:static lg:w-auto"
               :class="mobileNav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            @include('layouts.partials._sidebar')
        </aside>
        @endpersist

        {{-- Main area --}}
        <div class="min-w-0">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sm:px-6">
                @include('layouts.partials._topbar')
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
    {{-- Mobile overlay --}}
    <div x-show="mobileNav" x-transition.opacity @click="mobileNav=false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
    @fluxScripts
</body>
</html>
