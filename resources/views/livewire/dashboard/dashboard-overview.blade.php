<div class="space-y-6">
    <!-- Top Welcome Header -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Dashboard & Reports</span>
                <span class="text-slate-300 dark:text-slate-700">•</span>
                <span class="text-xs text-slate-500">SalesFlow CRM</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                Chào {{ str(auth()->user()?->name)->before(' ') }},
            </h1>
            <p class="mt-0.5 text-sm text-slate-500">
                Thống kê hiệu suất bán hàng thực tế và chỉ số KPI toàn hệ thống.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('create', App\Models\Lead::class)
                <flux:button href="{{ route('leads.create') }}" wire:navigate variant="subtle" icon="user-plus" size="sm">
                    + Lead mới
                </flux:button>
            @endcan

            @can('create', App\Models\Opportunity::class)
                <flux:button href="{{ route('opportunities.create') }}" wire:navigate variant="subtle" icon="currency-dollar" size="sm">
                    + Cơ hội mới
                </flux:button>
            @endcan

            @can('create', App\Models\Task::class)
                <flux:button href="{{ route('tasks.create') }}" wire:navigate variant="primary" icon="plus" size="sm">
                    + Tạo Công việc
                </flux:button>
            @endcan
        </div>
    </div>

    <!-- Filter Bar Card (Bộ lọc dùng chung) -->
    <div class="crm-card space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <flux:icon.funnel class="size-4 text-indigo-600 dark:text-indigo-400" />
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Bộ lọc báo cáo & KPI</h2>
            </div>

            <div class="flex items-center gap-2">
                <flux:button wire:click="clearCacheAndReload" variant="subtle" icon="arrow-path" size="sm">
                    Làm mới & Xóa Cache
                </flux:button>
                <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                    Đặt lại
                </flux:button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Khoảng thời gian -->
            <div>
                <flux:select wire:model.live="datePreset" label="Khoảng thời gian">
                    <option value="today">Hôm nay</option>
                    <option value="this_week">Tuần này</option>
                    <option value="this_month">Tháng này</option>
                    <option value="this_quarter">Quý này</option>
                    <option value="this_year">Năm nay</option>
                    <option value="custom">Tùy chọn khoảng ngày...</option>
                </flux:select>
            </div>

            <!-- Phòng ban -->
            <div>
                <flux:select wire:model.live="departmentId" label="Phòng ban">
                    <option value="">Tất cả phòng ban</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Nhân viên phụ trách -->
            <div>
                <flux:select wire:model.live="userId" label="Nhân viên phụ trách">
                    <option value="">Tất cả nhân viên</option>
                    @foreach ($this->users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Pipeline -->
            <div>
                <flux:select wire:model.live="pipelineId" label="Quy trình (Pipeline)">
                    <option value="">Tất cả Pipeline</option>
                    @foreach ($this->pipelines as $pipe)
                        <option value="{{ $pipe->id }}">{{ $pipe->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        @if ($datePreset === 'custom')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <flux:input type="date" wire:model.live="startDate" label="Từ ngày" />
                <flux:input type="date" wire:model.live="endDate" label="Đến ngày" />
            </div>
        @endif
    </div>

    <!-- Loading Indicator -->
    <div wire:loading.delay class="w-full">
        <div class="rounded-xl bg-indigo-50/80 p-3 text-center text-xs font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
            ⏳ Đang cập nhật dữ liệu KPI theo bộ lọc...
        </div>
    </div>

    @php
        $oppMetrics = $this->metrics['opportunity_metrics'];
        $leadMetrics = $this->metrics['lead_metrics'];
        $actTaskMetrics = $this->metrics['activity_task_metrics'];
    @endphp

    <!-- Top 4 Primary KPI Stat Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <!-- KPI 1: Doanh thu Thắng (Won Amount) -->
        <div class="crm-card relative overflow-hidden space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Doanh thu chốt Thắng</span>
                <span class="rounded-lg bg-emerald-100 p-2 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <flux:icon.banknotes class="size-5" />
                </span>
            </div>

            <div>
                <p class="text-2xl font-black text-slate-900 dark:text-white">
                    {{ number_format((float) $oppMetrics['won_amount']) }} đ
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Tổng giá trị thực tế của {{ $oppMetrics['won_opportunities'] }} Deal chốt Thắng
                </p>
            </div>
        </div>

        <!-- KPI 2: Dự báo Doanh thu Trọng số (Weighted Forecast) -->
        <div class="crm-card relative overflow-hidden space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Dự báo Trọng số</span>
                <span class="rounded-lg bg-indigo-100 p-2 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                    <flux:icon.chart-bar class="size-5" />
                </span>
            </div>

            <div>
                <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                    {{ number_format((float) $oppMetrics['weighted_forecast']) }} đ
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Tính theo xác suất % của các Stage trong Pipeline
                </p>
            </div>
        </div>

        <!-- KPI 3: Tỷ lệ Thắng (Win Rate %) -->
        <div class="crm-card relative overflow-hidden space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Tỷ lệ Thắng (Win Rate)</span>
                <span class="rounded-lg bg-amber-100 p-2 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                    <flux:icon.trophy class="size-5" />
                </span>
            </div>

            <div>
                <p class="text-2xl font-black text-amber-600 dark:text-amber-400">
                    {{ $oppMetrics['win_rate'] }}%
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $oppMetrics['won_opportunities'] }} Thắng / {{ $oppMetrics['won_opportunities'] + $oppMetrics['lost_opportunities'] }} Deal đã đóng
                </p>
            </div>
        </div>

        <!-- KPI 4: Chu kỳ bán hàng trung bình -->
        <div class="crm-card relative overflow-hidden space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Chu kỳ Bán hàng</span>
                <span class="rounded-lg bg-purple-100 p-2 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">
                    <flux:icon.clock class="size-5" />
                </span>
            </div>

            <div>
                <p class="text-2xl font-black text-purple-600 dark:text-purple-400">
                    {{ $oppMetrics['avg_sales_cycle_days'] }} ngày
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Thời gian trung bình từ khởi tạo đến chốt Won
                </p>
            </div>
        </div>
    </div>

    <!-- Secondary Metrics Section: Lead & Activity & Tasks -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Lead Performance Card -->
        <div class="crm-card space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <flux:icon.user-group class="size-5 text-indigo-600 dark:text-indigo-400" />
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Thống kê Khách hàng tiềm năng (Leads)</h3>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <p class="text-xs text-slate-500">Tổng Lead</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $leadMetrics['total_leads'] }}</p>
                </div>

                <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/40">
                    <p class="text-xs text-emerald-700 dark:text-emerald-400">Đã chuyển đổi</p>
                    <p class="text-xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">{{ $leadMetrics['converted_leads'] }}</p>
                </div>

                <div class="rounded-xl bg-indigo-50 p-3 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/40">
                    <p class="text-xs text-indigo-700 dark:text-indigo-400">Tỷ lệ chuyển đổi</p>
                    <p class="text-xl font-bold text-indigo-700 dark:text-indigo-300 mt-1">{{ $leadMetrics['conversion_rate'] }}%</p>
                </div>
            </div>

            <!-- Status breakdown -->
            <div class="space-y-2 pt-2">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Phân bổ theo Trạng thái Lead</h4>
                <div class="flex flex-wrap gap-2">
                    @forelse ($leadMetrics['by_status'] as $status => $count)
                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-300">
                            <span>{{ $status }}:</span>
                            <strong class="text-indigo-600 dark:text-indigo-400">{{ $count }}</strong>
                        </span>
                    @empty
                        <span class="text-xs text-slate-400 italic">Chưa có dữ liệu Lead</span>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Task & Activity Performance Card -->
        <div class="crm-card space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <flux:icon.check-circle class="size-5 text-indigo-600 dark:text-indigo-400" />
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Hoạt động & Công việc (Tasks)</h3>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <p class="text-xs text-slate-500">Hoạt động đã làm</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $actTaskMetrics['total_activities'] }}</p>
                </div>

                <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/40">
                    <p class="text-xs text-emerald-700 dark:text-emerald-400">Task hoàn thành</p>
                    <p class="text-xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">{{ $actTaskMetrics['completed_tasks'] }}</p>
                </div>

                <div class="rounded-xl bg-red-50 p-3 dark:bg-red-950/40 border border-red-100 dark:border-red-900/40">
                    <p class="text-xs text-red-700 dark:text-red-400">Task quá hạn ⚠️</p>
                    <p class="text-xl font-bold text-red-700 dark:text-red-300 mt-1">{{ $actTaskMetrics['overdue_tasks'] }}</p>
                </div>
            </div>

            <!-- Task Status breakdown -->
            <div class="space-y-2 pt-2">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Phân bổ Trạng thái Công việc</h4>
                <div class="flex flex-wrap gap-2">
                    @forelse ($actTaskMetrics['tasks_by_status'] as $st => $cnt)
                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-300">
                            <span>{{ $st }}:</span>
                            <strong class="text-indigo-600 dark:text-indigo-400">{{ $cnt }}</strong>
                        </span>
                    @empty
                        <span class="text-xs text-slate-400 italic">Chưa có dữ liệu Công việc</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
