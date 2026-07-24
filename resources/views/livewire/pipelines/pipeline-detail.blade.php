<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $pipeline->name }}</h1>

                @if ($pipeline->is_default)
                    <flux:badge variant="solid" color="emerald" size="sm">Quy trình Mặc định</flux:badge>
                @endif

                @if ($pipeline->is_active)
                    <flux:badge color="blue" size="sm">Hoạt động</flux:badge>
                @else
                    <flux:badge color="zinc" size="sm">Tạm ngừng</flux:badge>
                @endif
            </div>

            <p class="mt-1 text-sm text-slate-500">Mã quy trình: <code class="font-mono text-slate-700 dark:text-slate-300">{{ $pipeline->code }}</code></p>
        </div>

        <div class="flex items-center gap-3">
            <flux:button href="{{ route('pipelines.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Danh sách
            </flux:button>

            @can('update', $pipeline)
                <flux:button href="{{ route('pipelines.edit', $pipeline->id) }}" variant="primary" icon="pencil" size="sm">
                    Chỉnh sửa Quy trình
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Column: Các giai đoạn Kanban Preview -->
        <div class="lg:col-span-2 space-y-6">
            <div class="crm-card">
                <h2 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">Cấu trúc Giai đoạn ({{ $pipeline->stages->count() }} bước)</h2>

                <div class="space-y-3">
                    @foreach ($pipeline->stages as $stage)
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50/50 p-3.5 dark:border-slate-800 dark:bg-slate-900/50">
                            <div class="flex items-center gap-3">
                                <span class="flex size-7 items-center justify-center rounded-full text-xs font-bold text-white shrink-0" style="background-color: {{ $stage->color }};">
                                    {{ $stage->position }}
                                </span>

                                <div>
                                    <div class="font-semibold text-slate-900 dark:text-white text-sm">
                                        {{ $stage->name }}
                                    </div>
                                    @if ($stage->description)
                                        <div class="text-xs text-slate-500">{{ $stage->description }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">
                                    Xác suất: {{ $stage->probability }}%
                                </span>

                                @if ($stage->is_won)
                                    <flux:badge color="emerald" size="sm">Won</flux:badge>
                                @elseif ($stage->is_lost)
                                    <flux:badge color="red" size="sm">Lost</flux:badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Sidebar Info Column -->
        <div class="space-y-6">
            <div class="crm-card space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Thông tin tổng quan</h3>

                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-slate-500">Mô tả:</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200">{{ $pipeline->description ?: 'Chưa có mô tả' }}</p>
                    </div>

                    <div>
                        <span class="text-slate-500">Người phụ trách:</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200">{{ $pipeline->owner?->name ?: 'Hệ thống' }}</p>
                    </div>

                    <div>
                        <span class="text-slate-500">Phòng ban:</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200">{{ $pipeline->department?->name ?: 'Hệ thống' }}</p>
                    </div>

                    <div>
                        <span class="text-slate-500">Ngày khởi tạo:</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200">{{ $pipeline->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
