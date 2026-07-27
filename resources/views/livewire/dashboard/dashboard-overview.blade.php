<div class="space-y-6">
    @include('livewire.reports.partials.header', [
        'title' => 'Tổng quan bán hàng',
        'description' => 'Theo dõi khách hàng tiềm năng, doanh thu, dự báo và công việc trong cùng một phạm vi dữ liệu.',
    ])

    @include('livewire.reports.partials.filters')

    @php
        $lead = $this->metrics['lead_metrics'];
        $opportunity = $this->metrics['opportunity_metrics'];
        $work = $this->metrics['activity_task_metrics'];
        $leadStatusChart = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($lead['by_status']),
                'datasets' => [[
                    'label' => 'Khách hàng tiềm năng',
                    'data' => array_values($lead['by_status']),
                    'backgroundColor' => '#4f46e5',
                    'borderRadius' => 6,
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
                'scales' => ['x' => ['grid' => ['display' => false]], 'y' => ['beginAtZero' => true]],
            ],
        ];
        $leadSourceChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => array_keys($lead['by_source']),
                'datasets' => [[
                    'label' => 'Khách hàng tiềm năng',
                    'data' => array_values($lead['by_source']),
                    'backgroundColor' => ['#4f46e5', '#0f766e', '#0369a1', '#a16207', '#be123c', '#64748b'],
                    'borderWidth' => 0,
                ]],
            ],
            'options' => ['cutout' => '62%'],
        ];
    @endphp

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Chỉ số chính">
        <div
            wire:click="$dispatch('open-report-drilldown', { type: 'won_opportunities' })"
            class="crm-card cursor-pointer transition hover:border-emerald-500 hover:shadow-md dark:hover:border-emerald-500"
            title="Bấm xem chi tiết các cơ hội chốt thành công"
        >
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">Doanh thu đã chốt</p>
                <flux:badge color="emerald" size="xs">Soi chi tiết ➔</flux:badge>
            </div>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($opportunity['won_amount']) }} đ</p>
            <p class="mt-1 text-xs text-slate-500">{{ $opportunity['won_opportunities'] }} cơ hội thắng trong kỳ</p>
        </div>

        <div
            wire:click="$dispatch('open-report-drilldown', { type: 'open_opportunities' })"
            class="crm-card cursor-pointer transition hover:border-emerald-500 hover:shadow-md dark:hover:border-emerald-500"
            title="Bấm xem chi tiết các cơ hội đang mở"
        >
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">Dự báo có trọng số</p>
                <flux:badge color="zinc" size="xs">Soi chi tiết ➔</flux:badge>
            </div>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($opportunity['weighted_forecast']) }} đ</p>
            <p class="mt-1 text-xs text-slate-500">{{ $opportunity['open_opportunities'] }} cơ hội dự kiến chốt trong kỳ</p>
        </div>

        <div
            wire:click="$dispatch('open-report-drilldown', { type: 'new_leads' })"
            class="crm-card cursor-pointer transition hover:border-emerald-500 hover:shadow-md dark:hover:border-emerald-500"
            title="Bấm xem chi tiết danh sách Lead chưa chuyển đổi"
        >
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">Tỷ lệ thắng</p>
                <flux:badge color="zinc" size="xs">Xem Lead ➔</flux:badge>
            </div>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $opportunity['win_rate'] }}%</p>
            <p class="mt-1 text-xs text-slate-500">Tính trên cơ hội đã đóng trong kỳ</p>
        </div>

        <div
            wire:click="$dispatch('open-report-drilldown', { type: 'overdue_tasks' })"
            class="crm-card cursor-pointer transition hover:border-red-500 hover:shadow-md dark:hover:border-red-500"
            title="Bấm xem chi tiết công việc quá hạn"
        >
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">Chu kỳ bán hàng trung bình</p>
                <flux:badge color="red" size="xs">Xem Task quá hạn ➔</flux:badge>
            </div>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $opportunity['avg_sales_cycle_days'] }} ngày</p>
            <p class="mt-1 text-xs text-slate-500">Từ lúc tạo đến khi thắng</p>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @if ($lead['by_status'] !== [])
            <div wire:key="lead-status-{{ md5(json_encode($leadStatusChart)) }}">
                <x-reports.chart
                    title="Khách hàng tiềm năng theo trạng thái"
                    description="Số lượng được tạo trong khoảng thời gian đã chọn."
                    :config="$leadStatusChart"
                />
            </div>
        @else
            <div class="crm-card flex min-h-72 items-center justify-center text-sm text-slate-500">Chưa có dữ liệu trạng thái khách hàng tiềm năng.</div>
        @endif

        @if ($lead['by_source'] !== [])
            <div wire:key="lead-source-{{ md5(json_encode($leadSourceChart)) }}">
                <x-reports.chart
                    title="Khách hàng tiềm năng theo nguồn"
                    description="Tỷ trọng nguồn mang lại khách hàng tiềm năng trong kỳ."
                    :config="$leadSourceChart"
                />
            </div>
        @else
            <div class="crm-card flex min-h-72 items-center justify-center text-sm text-slate-500">Chưa có dữ liệu nguồn khách hàng tiềm năng.</div>
        @endif
    </div>

    <section class="crm-card space-y-4">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Hoạt động và công việc</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tổng hợp công việc theo ngày tạo và hoàn thành thực tế trong kỳ.</p>
        </div>
        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div><dt class="text-sm text-slate-500">Hoạt động</dt><dd class="mt-1 text-xl font-semibold">{{ $work['total_activities'] }}</dd></div>
            <div><dt class="text-sm text-slate-500">Công việc mới</dt><dd class="mt-1 text-xl font-semibold">{{ $work['total_tasks'] }}</dd></div>
            <div><dt class="text-sm text-slate-500">Đã hoàn thành</dt><dd class="mt-1 text-xl font-semibold text-emerald-600">{{ $work['completed_tasks'] }}</dd></div>
            <div
                wire:click="$dispatch('open-report-drilldown', { type: 'overdue_tasks' })"
                class="cursor-pointer rounded-lg p-2 hover:bg-red-50 dark:hover:bg-red-950/30 transition"
            >
                <dt class="text-sm text-red-600 font-medium">Đang quá hạn (Soi ➔)</dt>
                <dd class="mt-1 text-xl font-semibold text-red-600">{{ $work['overdue_tasks'] }}</dd>
            </div>
        </dl>
    </section>

    <!-- Drill-Down Modal -->
    <livewire:reports.report-drill-down-modal />
</div>
