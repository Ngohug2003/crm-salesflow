<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Tổng quan / Hôm nay</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Chào {{ str(auth()->user()->name)->before(' ') }},</h1>
            <p class="mt-2 text-slate-500">Nền tảng SalesFlow đã sẵn sàng. Dữ liệu CRM sẽ được thêm theo từng phase.</p>
        </div>
        <flux:button variant="primary" disabled>+ Tạo nhanh</flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Lead mới', '—', 'Chờ Phase 3'], ['Pipeline', '—', 'Chờ Phase 5'], ['Doanh thu dự kiến', '—', 'Chờ Phase 7'], ['Task quá hạn', '—', 'Chờ Phase 6']] as [$label, $value, $note])
            <div class="crm-card">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-4 text-3xl font-semibold">{{ $value }}</p>
                <p class="mt-3 text-xs text-slate-400">{{ $note }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        <div class="crm-card min-h-80">
            <div class="flex items-center justify-between"><h2 class="font-semibold">Doanh thu theo tháng</h2><span class="status-chip">Foundation</span></div>
            <div class="grid min-h-64 place-items-center text-center text-sm text-slate-500"><div><div class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-slate-100 dark:bg-slate-800">⌁</div><p>Biểu đồ sẽ dùng dữ liệu thật ở Phase 7.</p></div></div>
        </div>
        <div class="crm-card min-h-80">
            <h2 class="font-semibold">Trạng thái hệ thống</h2>
            <ul class="mt-5 space-y-4 text-sm">
                @foreach (['Fortify authentication', 'Livewire + Flux UI', 'PostgreSQL + Redis', 'Horizon + Reverb'] as $item)
                    <li class="flex items-center gap-3"><span class="size-2 rounded-full bg-emerald-400"></span><span>{{ $item }}</span></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
