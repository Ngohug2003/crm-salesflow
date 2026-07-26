<div class="space-y-6">
    @include('livewire.reports.partials.header', [
        'title' => 'Hiệu suất bán hàng',
        'description' => 'So sánh kết quả của đội ngũ theo doanh thu thực tế, tỷ lệ thắng, hoạt động và công việc hoàn thành.',
    ])

    @include('livewire.reports.partials.filters')

    @php
        $performance = $this->performanceData;
        $chartRows = array_slice($performance, 0, 10);
        $chart = [
            'type' => 'bar',
            'currency' => true,
            'data' => [
                'labels' => array_column($chartRows, 'user_name'),
                'datasets' => [[
                    'label' => 'Doanh thu đã chốt',
                    'data' => array_column($chartRows, 'won_amount'),
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

    @if ($performance !== [])
        <div wire:key="performance-chart-{{ md5(json_encode($chart)) }}">
            <x-reports.chart
                title="Doanh thu theo nhân viên"
                description="Tối đa 10 nhân viên có doanh thu đã chốt cao nhất trong kỳ."
                :config="$chart"
                height="h-96"
            />
        </div>

        <section class="crm-card space-y-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Chi tiết hiệu suất</h2>
                <p class="mt-1 text-sm text-slate-500">Công việc hoàn thành được tính theo ngày hoàn thành và danh sách người được giao.</p>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Hạng</flux:table.column>
                    <flux:table.column>Nhân viên</flux:table.column>
                    <flux:table.column align="end">Khách hàng tiềm năng</flux:table.column>
                    <flux:table.column align="end">Thắng / Cơ hội mới</flux:table.column>
                    <flux:table.column align="end">Doanh thu</flux:table.column>
                    <flux:table.column align="end">Tỷ lệ thắng</flux:table.column>
                    <flux:table.column align="end">Hoạt động</flux:table.column>
                    <flux:table.column align="end">Công việc xong</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($performance as $rank => $row)
                        <flux:table.row :key="$row['user_id']">
                            <flux:table.cell>{{ $rank + 1 }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $row['user_name'] }}</div>
                                <div class="text-xs text-slate-500">{{ $row['department_name'] }}</div>
                            </flux:table.cell>
                            <flux:table.cell align="end">{{ $row['total_leads'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $row['won_opportunities'] }} / {{ $row['total_opportunities'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($row['won_amount']) }} đ</flux:table.cell>
                            <flux:table.cell align="end">{{ $row['win_rate'] }}%</flux:table.cell>
                            <flux:table.cell align="end">{{ $row['activity_count'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $row['completed_tasks_count'] }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </section>
    @else
        <section class="crm-card flex min-h-72 flex-col items-center justify-center text-center">
            <flux:icon.user-group class="size-8 text-slate-400" />
            <h2 class="mt-3 font-medium text-slate-900 dark:text-white">Chưa có dữ liệu hiệu suất</h2>
            <p class="mt-1 text-sm text-slate-500">Hãy thay đổi bộ lọc hoặc bổ sung dữ liệu bán hàng trong kỳ.</p>
        </section>
    @endif
</div>
