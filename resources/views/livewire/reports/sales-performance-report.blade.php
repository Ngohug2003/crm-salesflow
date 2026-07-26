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
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Báo cáo Hiệu suất Sales & Bảng xếp hạng</h1>
            <p class="mt-0.5 text-sm text-slate-500">Đo lường năng suất làm việc và hiệu quả bán hàng thực tế của đội ngũ Sales.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button href="{{ route('reports.revenue') }}" wire:navigate variant="subtle" icon="banknotes" size="sm">
                Báo cáo Doanh thu
            </flux:button>
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
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Bộ lọc hiệu suất Sales</h2>
            </div>

            <flux:button wire:click="resetFilters" variant="ghost" icon="arrow-path" size="sm">
                Đặt lại bộ lọc
            </flux:button>
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
                <flux:select wire:model.live="userId" label="Nhân viên">
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
            ⏳ Đang tổng hợp hiệu suất & Bảng xếp hạng Sales...
        </div>
    </div>

    @php
        $performance = $this->performanceData;
        $top3 = array_slice($performance, 0, 3);
    @endphp

    <!-- Leaderboard Top Performers Card -->
    <div class="crm-card space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <flux:icon.trophy class="size-5 text-amber-500" />
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Bảng xếp hạng Top Sales (Leaderboard)</h2>
            </div>
            <span class="text-xs text-slate-500">Sắp xếp theo Doanh thu Won thực tế</span>
        </div>

        @if (count($top3) > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 pt-2">
                @php
                    $badges = [
                        0 => ['label' => 'Quán quân 🥇', 'border' => 'border-amber-300 dark:border-amber-600/60 bg-amber-50/40 dark:bg-amber-950/20', 'badge' => 'bg-amber-500 text-white'],
                        1 => ['label' => 'Á quân 🥈', 'border' => 'border-slate-300 dark:border-slate-700 bg-slate-50/40 dark:bg-slate-900/40', 'badge' => 'bg-slate-400 text-white'],
                        2 => ['label' => 'Hạng 3 🥉', 'border' => 'border-orange-300 dark:border-orange-700/60 bg-orange-50/40 dark:bg-orange-950/20', 'badge' => 'bg-orange-500 text-white'],
                    ];
                @endphp

                @foreach ($top3 as $idx => $topRep)
                    @php
                        $cfg = $badges[$idx] ?? $badges[2];
                    @endphp
                    <div class="relative rounded-2xl border p-4 shadow-sm flex flex-col justify-between space-y-3 {{ $cfg['border'] }}">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-0.5 rounded-full {{ $cfg['badge'] }}">
                                {{ $cfg['label'] }}
                            </span>
                            <span class="text-xs font-medium text-slate-500">{{ $topRep['department_name'] }}</span>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $topRep['user_name'] }}</h3>
                            <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">
                                {{ number_format((float) $topRep['won_amount']) }} đ
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-2 border-t border-slate-200/60 dark:border-slate-800 pt-2 text-center text-xs">
                            <div>
                                <span class="text-slate-400 block">Deals Won</span>
                                <strong class="text-slate-900 dark:text-white">{{ $topRep['won_opportunities'] }}/{{ $topRep['total_opportunities'] }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block">Win Rate</span>
                                <strong class="text-emerald-600 dark:text-emerald-400">{{ $topRep['win_rate'] }}%</strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block">Hoạt động</span>
                                <strong class="text-indigo-600 dark:text-indigo-400">{{ $topRep['activity_count'] }}</strong>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-8 text-center text-xs text-slate-400 italic">Chưa có dữ liệu bảng xếp hạng.</div>
        @endif
    </div>

    <!-- Detailed Sales Performance Data Table Card -->
    <div class="crm-card space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
            Bảng chi tiết Hiệu suất theo Nhân viên
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-semibold text-slate-500 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3"># Hạng</th>
                        <th class="px-4 py-3">Nhân viên Sales</th>
                        <th class="px-4 py-3">Phòng ban</th>
                        <th class="px-4 py-3 text-center">Số Lead</th>
                        <th class="px-4 py-3 text-center">Deal Won / Tổng</th>
                        <th class="px-4 py-3 text-right">Doanh thu Won (đ)</th>
                        <th class="px-4 py-3 text-center">Win Rate (%)</th>
                        <th class="px-4 py-3 text-center">Hoạt động</th>
                        <th class="px-4 py-3 text-center">Task hoàn thành</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse ($performance as $rank => $rep)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-bold">
                                @if ($rank === 0)
                                    <span class="inline-flex size-6 items-center justify-center rounded-full bg-amber-100 text-amber-800 font-extrabold text-xs">1</span>
                                @elseif ($rank === 1)
                                    <span class="inline-flex size-6 items-center justify-center rounded-full bg-slate-200 text-slate-800 font-extrabold text-xs">2</span>
                                @elseif ($rank === 2)
                                    <span class="inline-flex size-6 items-center justify-center rounded-full bg-orange-100 text-orange-800 font-extrabold text-xs">3</span>
                                @else
                                    <span class="text-slate-400 pl-2">#{{ $rank + 1 }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">
                                {{ $rep['user_name'] }}
                            </td>
                            <td class="px-4 py-3 text-slate-500">
                                {{ $rep['department_name'] }}
                            </td>
                            <td class="px-4 py-3 text-center font-semibold">{{ $rep['total_leads'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $rep['won_opportunities'] }}</span> / {{ $rep['total_opportunities'] }}
                            </td>
                            <td class="px-4 py-3 text-right font-black text-indigo-600 dark:text-indigo-400">
                                {{ number_format((float) $rep['won_amount']) }} đ
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-purple-600 dark:text-purple-400">
                                {{ $rep['win_rate'] }}%
                            </td>
                            <td class="px-4 py-3 text-center font-mono">{{ $rep['activity_count'] }}</td>
                            <td class="px-4 py-3 text-center font-mono text-emerald-600 dark:text-emerald-400">{{ $rep['completed_tasks_count'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-slate-400 italic">Không có dữ liệu nhân viên.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
