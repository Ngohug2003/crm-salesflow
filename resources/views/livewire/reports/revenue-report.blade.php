<div class="space-y-6">
    @include('livewire.reports.partials.header', [
        'title' => 'Doanh thu và dự báo',
        'description' => 'Doanh thu dựa trên ngày chốt thực tế; dự báo dựa trên ngày dự kiến chốt của các cơ hội đang mở.',
    ])

    @include('livewire.reports.partials.filters')

    @php
        $metrics = $this->opportunityMetrics;
        $series = $metrics['revenue_series'];
        $revenueChart = [
            'type' => 'bar',
            'currency' => true,
            'data' => [
                'labels' => $series['labels'],
                'datasets' => [
                    [
                        'label' => 'Doanh thu đã chốt',
                        'data' => $series['won'],
                        'backgroundColor' => '#0f766e',
                        'borderRadius' => 6,
                    ],
                    [
                        'label' => 'Dự báo có trọng số',
                        'data' => $series['forecast'],
                        'backgroundColor' => '#4f46e5',
                        'borderRadius' => 6,
                    ],
                ],
            ],
            'options' => [
                'scales' => ['x' => ['grid' => ['display' => false]], 'y' => ['beginAtZero' => true]],
            ],
        ];
        $lossChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => array_keys($metrics['loss_reasons']),
                'datasets' => [[
                    'label' => 'Cơ hội thua',
                    'data' => array_values($metrics['loss_reasons']),
                    'backgroundColor' => ['#be123c', '#c2410c', '#a16207', '#64748b', '#0369a1'],
                    'borderWidth' => 0,
                ]],
            ],
            'options' => ['cutout' => '62%'],
        ];
    @endphp

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Chỉ số doanh thu">
        <div class="crm-card">
            <p class="text-sm text-slate-500">Doanh thu đã chốt</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($metrics['won_amount']) }} đ</p>
            <p class="mt-1 text-xs text-slate-500">{{ $metrics['won_opportunities'] }} cơ hội thắng trong kỳ</p>
        </div>
        <div class="crm-card">
            <p class="text-sm text-slate-500">Dự báo có trọng số</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($metrics['weighted_forecast']) }} đ</p>
            <p class="mt-1 text-xs text-slate-500">Chỉ gồm cơ hội đang mở dự kiến chốt trong kỳ</p>
        </div>
        <div class="crm-card">
            <p class="text-sm text-slate-500">Giá trị đang mở</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($metrics['open_amount']) }} đ</p>
            <p class="mt-1 text-xs text-slate-500">{{ $metrics['open_opportunities'] }} cơ hội dự kiến chốt</p>
        </div>
        <div class="crm-card">
            <p class="text-sm text-slate-500">Tỷ lệ thắng</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $metrics['win_rate'] }}%</p>
            <p class="mt-1 text-xs text-slate-500">Chu kỳ trung bình {{ $metrics['avg_sales_cycle_days'] }} ngày</p>
        </div>
    </section>

    @if ($series['labels'] !== [])
        <div wire:key="revenue-chart-{{ md5(json_encode($revenueChart)) }}">
            <x-reports.chart
                title="Doanh thu và dự báo theo tháng"
                description="So sánh giá trị đã chốt với dự báo có trọng số của cơ hội đang mở."
                :config="$revenueChart"
            />
        </div>
    @else
        <div class="crm-card flex min-h-72 items-center justify-center text-sm text-slate-500">Chưa có doanh thu hoặc dự báo trong khoảng thời gian này.</div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-1">
            @if ($metrics['loss_reasons'] !== [])
                <div wire:key="loss-chart-{{ md5(json_encode($lossChart)) }}">
                    <x-reports.chart
                        title="Lý do thất bại"
                        description="Phân bổ các cơ hội thua được đóng trong kỳ."
                        :config="$lossChart"
                    />
                </div>
            @else
                <div class="crm-card flex min-h-72 items-center justify-center text-center text-sm text-slate-500">Chưa có lý do thất bại trong kỳ.</div>
            @endif
        </div>

        <section class="crm-card space-y-4 xl:col-span-2">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Dự báo theo giai đoạn</h2>
                <p class="mt-1 text-sm text-slate-500">Giá trị có trọng số được tính tại backend theo xác suất của giai đoạn.</p>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Giai đoạn</flux:table.column>
                    <flux:table.column align="end">Cơ hội</flux:table.column>
                    <flux:table.column align="end">Giá trị</flux:table.column>
                    <flux:table.column align="end">Dự báo</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($metrics['forecast_by_stage'] as $row)
                        <flux:table.row :key="$row['stage_id']">
                            <flux:table.cell>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $row['stage_name'] }}</div>
                                <div class="text-xs text-slate-500">Xác suất {{ $row['probability'] }}%</div>
                            </flux:table.cell>
                            <flux:table.cell align="end">{{ $row['opportunity_count'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($row['total_amount']) }} đ</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($row['weighted_amount']) }} đ</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">Chưa có cơ hội đang mở dự kiến chốt trong kỳ.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </section>
    </div>

    <!-- Thống kê Dự báo theo Forecast Category -->
    @if (!empty($metrics['forecast_by_category']))
        <section class="crm-card space-y-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Dự báo theo Danh mục (Forecast Category)</h2>
                <p class="mt-1 text-sm text-slate-500">Phân bổ giá trị cơ hội đang mở theo khả năng chốt đơn (Cam kết chốt, Kịch bản tối ưu, Pipeline, Loại trừ).</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($metrics['forecast_by_category'] as $fcItem)
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                        <div class="flex items-center justify-between">
                            <flux:badge :color="$fcItem['color']" size="sm">{{ $fcItem['label'] }}</flux:badge>
                            <span class="text-xs text-slate-400">{{ $fcItem['count'] }} cơ hội</span>
                        </div>
                        <p class="mt-2 text-lg font-bold text-slate-900 dark:text-white">{{ number_format($fcItem['total_amount']) }} đ</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
