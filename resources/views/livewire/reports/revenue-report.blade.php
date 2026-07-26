<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" wire:navigate class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 hover:underline">
                    ← Dashboard
                </a>
                <span class="text-slate-300 dark:text-slate-700">•</span>
                <span class="text-xs text-slate-500">Sales Analytics</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Báo cáo Doanh thu & Dự báo (Revenue & Forecast)</h1>
            <p class="mt-0.5 text-sm text-slate-500">Phân tích doanh thu thực tế, dự báo trọng số và cấu trúc thất bại trong bán hàng.</p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button href="{{ route('reports.funnel') }}" wire:navigate variant="subtle" icon="chart-bar" size="sm">
                Báo cáo Phễu (Funnel)
            </flux:button>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="crm-card space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <flux:icon.funnel class="size-4 text-indigo-600 dark:text-indigo-400" />
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Bộ lọc báo cáo Doanh thu</h2>
            </div>

            <flux:button wire:click="resetFilters" variant="ghost" icon="arrow-path" size="sm">
                Đặt lại bộ lọc
            </flux:button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Pipeline Select -->
            <div>
                <flux:select wire:model.live="pipelineId" label="Quy trình (Pipeline)">
                    <option value="">Tất cả Pipeline</option>
                    @foreach ($this->pipelines as $pipe)
                        <option value="{{ $pipe->id }}">{{ $pipe->name }}</option>
                    @endforeach
                </flux:select>
            </div>

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
        </div>

        @if ($datePreset === 'custom')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <flux:input type="date" wire:model.live="startDate" label="Từ ngày" />
                <flux:input type="date" wire:model.live="endDate" label="Đến ngày" />
            </div>
        @endif
    </div>

    <!-- Loading State -->
    <div wire:loading.delay class="w-full">
        <div class="rounded-xl bg-indigo-50/80 p-3 text-center text-xs font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
            ⏳ Đang tính toán số liệu Doanh thu & Dự báo...
        </div>
    </div>

    @php
        $opp = $this->opportunityMetrics;
    @endphp

    <!-- Top 4 Financial KPI Stat Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <!-- Won Amount Card -->
        <div class="crm-card relative overflow-hidden space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Doanh thu chốt Thắng</span>
                <span class="rounded-lg bg-emerald-100 p-2 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <flux:icon.banknotes class="size-5" />
                </span>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900 dark:text-white">
                    {{ number_format((float) $opp['won_amount']) }} đ
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Thực thu từ {{ $opp['won_opportunities'] }} hợp đồng Won
                </p>
            </div>
        </div>

        <!-- Weighted Forecast Card -->
        <div class="crm-card relative overflow-hidden space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Dự báo Trọng số</span>
                <span class="rounded-lg bg-indigo-100 p-2 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                    <flux:icon.chart-bar class="size-5" />
                </span>
            </div>
            <div>
                <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                    {{ number_format((float) $opp['weighted_forecast']) }} đ
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Giá trị kỳ vọng tính theo % xác suất Stage
                </p>
            </div>
        </div>

        <!-- Open Pipeline Value Card -->
        <div class="crm-card relative overflow-hidden space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pipeline Đang mở</span>
                <span class="rounded-lg bg-blue-100 p-2 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                    <flux:icon.currency-dollar class="size-5" />
                </span>
            </div>
            <div>
                <p class="text-2xl font-black text-blue-600 dark:text-blue-400">
                    {{ number_format((float) $opp['open_amount']) }} đ
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Tổng giá trị của {{ $opp['open_opportunities'] }} Deal đang theo đuổi
                </p>
            </div>
        </div>

        <!-- Win Rate Card -->
        <div class="crm-card relative overflow-hidden space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Tỷ lệ Thắng (Win Rate)</span>
                <span class="rounded-lg bg-amber-100 p-2 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                    <flux:icon.trophy class="size-5" />
                </span>
            </div>
            <div>
                <p class="text-2xl font-black text-amber-600 dark:text-amber-400">
                    {{ $opp['win_rate'] }}%
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Chu kỳ bán hàng TB: {{ $opp['avg_sales_cycle_days'] }} ngày
                </p>
            </div>
        </div>
    </div>

    <!-- Secondary Grid: Loss Reasons & Stage Forecast Breakdown -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Loss Reasons Breakdown (1 Col) -->
        <div class="crm-card space-y-4 lg:col-span-1">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <flux:icon.exclamation-triangle class="size-5 text-red-500" />
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Lý do Thua Deal (Loss Reasons)</h3>
                </div>
                <span class="text-xs font-bold text-red-600 dark:text-red-400">{{ $opp['lost_opportunities'] }} Deal đã đóng Lost</span>
            </div>

            <div class="space-y-3">
                @php
                    $totalLost = max(1, $opp['lost_opportunities']);
                @endphp
                @forelse ($opp['loss_reasons'] as $reason => $count)
                    @php
                        $pct = round(($count / $totalLost) * 100, 1);
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-700 dark:text-slate-300">{{ $reason }}</span>
                            <span class="text-slate-500">{{ $count }} deal ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                            <div class="bg-red-500 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs text-slate-400 italic">Chưa có lý do thất bại nào được ghi nhận.</p>
                @endforelse
            </div>
        </div>

        <!-- Stage Forecast Breakdown Table (2 Cols) -->
        <div class="crm-card space-y-4 lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Chi tiết Doanh thu Dự báo theo Stage</h3>
                <span class="text-xs text-slate-500">Giá trị trọng số = Giá trị x Xác suất %</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-semibold text-slate-500 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="px-4 py-3">Stage</th>
                            <th class="px-4 py-3 text-center">Xác suất</th>
                            <th class="px-4 py-3 text-center">Số Deal</th>
                            <th class="px-4 py-3 text-right">Tổng giá trị (đ)</th>
                            <th class="px-4 py-3 text-right">Dự báo Trọng số (đ)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse ($this->funnelData as $stg)
                            @php
                                $weighted = round(($stg['total_amount'] * $stg['probability']) / 100, 2);
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-3 font-semibold">
                                    <div class="flex items-center gap-2">
                                        <span class="size-2.5 rounded-full" style="background-color: {{ $stg['color'] }};"></span>
                                        <span>{{ $stg['stage_name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono">{{ $stg['probability'] }}%</td>
                                <td class="px-4 py-3 text-center font-bold text-slate-900 dark:text-white">{{ $stg['opportunity_count'] }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{{ number_format($stg['total_amount']) }} đ</td>
                                <td class="px-4 py-3 text-right font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($weighted) }} đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-400 italic">Không có dữ liệu giai đoạn.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
