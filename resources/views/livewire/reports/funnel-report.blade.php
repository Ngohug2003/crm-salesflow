<div class="space-y-6">
    @include('livewire.reports.partials.header', [
        'title' => 'Phễu chuyển đổi',
        'description' => 'Đo tỷ lệ cơ hội đã thực sự đi qua từng giai đoạn dựa trên lịch sử chuyển giai đoạn.',
    ])

    @include('livewire.reports.partials.filters', ['pipelineRequired' => true])

    @php
        $funnel = $this->funnelData;
        $chart = [
            'type' => 'bar',
            'data' => [
                'labels' => array_column($funnel, 'stage_name'),
                'datasets' => [[
                    'label' => 'Cơ hội đã đi qua',
                    'data' => array_column($funnel, 'opportunity_count'),
                    'backgroundColor' => '#4f46e5',
                    'borderRadius' => 6,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'plugins' => ['legend' => ['display' => false]],
                'scales' => ['x' => ['beginAtZero' => true], 'y' => ['grid' => ['display' => false]]],
            ],
        ];
    @endphp

    @if ($funnel !== [])
        <div wire:key="funnel-chart-{{ md5(json_encode($chart)) }}">
            <x-reports.chart
                title="Số cơ hội đã đi qua từng giai đoạn"
                description="Mỗi cơ hội chỉ được tính một lần tại mỗi giai đoạn trong quy trình đã chọn."
                :config="$chart"
            />
        </div>

        <section class="crm-card space-y-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Chi tiết chuyển đổi</h2>
                <p class="mt-1 text-sm text-slate-500">Tỷ lệ được tính trên tập cơ hội tạo trong khoảng thời gian đã chọn.</p>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Giai đoạn</flux:table.column>
                    <flux:table.column align="end">Cơ hội</flux:table.column>
                    <flux:table.column align="end">Giá trị</flux:table.column>
                    <flux:table.column align="end">So với bước trước</flux:table.column>
                    <flux:table.column align="end">So với đầu phễu</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($funnel as $row)
                        <flux:table.row :key="$row['stage_id']">
                            <flux:table.cell>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $row['stage_name'] }}</div>
                                <div class="text-xs text-slate-500">Xác suất cấu hình {{ $row['probability'] }}%</div>
                            </flux:table.cell>
                            <flux:table.cell align="end">{{ $row['opportunity_count'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($row['total_amount']) }} đ</flux:table.cell>
                            <flux:table.cell align="end">{{ $row['conversion_from_previous'] }}%</flux:table.cell>
                            <flux:table.cell align="end">{{ $row['conversion_from_top'] }}%</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </section>
    @else
        <section class="crm-card flex min-h-72 flex-col items-center justify-center text-center">
            <flux:icon.chart-bar class="size-8 text-slate-400" />
            <h2 class="mt-3 font-medium text-slate-900 dark:text-white">Chưa có dữ liệu phễu</h2>
            <p class="mt-1 text-sm text-slate-500">Hãy chọn quy trình khác hoặc mở rộng khoảng thời gian.</p>
        </section>
    @endif
</div>
