<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <a href="{{ route('pipelines.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Quy trình bán hàng</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Chi tiết</span>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ $pipeline->name }}</h1>

                @if ($pipeline->is_default)
                    <flux:badge variant="solid" color="emerald" size="sm">Mặc định</flux:badge>
                @endif

                @if ($pipeline->is_active)
                    <flux:badge color="emerald" size="sm">Hoạt động</flux:badge>
                @else
                    <flux:badge color="zinc" size="sm">Tạm ngừng</flux:badge>
                @endif
            </div>

            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Mã quy trình: <code class="font-mono text-slate-700 dark:text-slate-300">{{ $pipeline->code }}</code>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('pipelines.index')" wire:navigate variant="ghost">
                Danh sách
            </flux:button>

            @can('update', $pipeline)
                <flux:button :href="route('pipelines.edit', $pipeline->id)" wire:navigate variant="primary">
                    Chỉnh sửa
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="crm-card space-y-5" aria-labelledby="pipeline-stages-title">
            <div>
                <h2 id="pipeline-stages-title" class="text-base font-semibold text-slate-950 dark:text-white">Cấu trúc giai đoạn</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $pipeline->stages->count() }} bước trong quy trình bán hàng này.</p>
            </div>

            <div class="space-y-3">
                @foreach ($pipeline->stages as $stage)
                    <article class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-3">
                                <span class="mt-1 grid size-7 shrink-0 place-items-center rounded-full text-xs font-semibold text-white" style="background-color: {{ $stage->color }};">
                                    {{ $stage->position }}
                                </span>

                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-semibold text-slate-950 dark:text-white">{{ $stage->name }}</h3>
                                        @if ($stage->is_won)
                                            <flux:badge color="emerald" size="sm">Thắng</flux:badge>
                                        @elseif ($stage->is_lost)
                                            <flux:badge color="red" size="sm">Thua</flux:badge>
                                        @endif
                                    </div>

                                    @if ($stage->description)
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $stage->description }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="text-sm text-slate-600 dark:text-slate-400">
                                Xác suất <strong class="text-slate-950 dark:text-white">{{ $stage->probability }}%</strong>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <aside class="crm-card h-fit space-y-4" aria-labelledby="pipeline-summary-title">
            <h2 id="pipeline-summary-title" class="text-base font-semibold text-slate-950 dark:text-white">Thông tin tổng quan</h2>

            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Mô tả</dt>
                    <dd class="mt-1 font-medium text-slate-800 dark:text-slate-200">{{ $pipeline->description ?: 'Chưa có mô tả' }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Người phụ trách</dt>
                    <dd class="mt-1 font-medium text-slate-800 dark:text-slate-200">{{ $pipeline->owner?->name ?: 'Hệ thống' }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Phòng ban</dt>
                    <dd class="mt-1 font-medium text-slate-800 dark:text-slate-200">{{ $pipeline->department?->name ?: 'Hệ thống' }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Ngày khởi tạo</dt>
                    <dd class="mt-1 font-medium text-slate-800 dark:text-slate-200">{{ $pipeline->created_at?->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </aside>
    </div>
</div>
