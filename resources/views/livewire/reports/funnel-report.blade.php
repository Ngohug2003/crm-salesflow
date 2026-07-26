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
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Báo cáo Phễu chuyển đổi (Funnel Report)</h1>
            <p class="mt-0.5 text-sm text-slate-500">Theo dõi tỷ lệ cơ hội di chuyển qua từng giai đoạn của Quy trình bán hàng.</p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button href="{{ route('dashboard') }}" wire:navigate variant="subtle" icon="home" size="sm">
                Trang chủ Dashboard
            </flux:button>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="crm-card space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <flux:icon.funnel class="size-4 text-indigo-600 dark:text-indigo-400" />
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Bộ lọc phễu chuyển đổi</h2>
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
            <!-- Pipeline Select -->
            <div>
                <flux:select wire:model.live="pipelineId" label="Quy trình (Pipeline) *">
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
            ⏳ Đang tải dữ liệu Phễu chuyển đổi...
        </div>
    </div>

    <!-- Funnel Visualization Card -->
    <div class="crm-card space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Biểu đồ Phễu chuyển đổi</h3>
            <span class="text-xs text-slate-500">Tỷ lệ % quy đổi giảm dần theo từng Stage</span>
        </div>

        @php
            $funnel = $this->funnelData;
            $maxCount = collect($funnel)->max('opportunity_count') ?: 1;
        @endphp

        @if (count($funnel) > 0)
            <div class="space-y-4 max-w-4xl mx-auto py-2">
                @foreach ($funnel as $index => $stg)
                    @php
                        $widthPercent = max(18, round(($stg['opportunity_count'] / $maxCount) * 100));
                    @endphp
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <div class="flex items-center gap-2">
                                <span class="size-3 rounded-full shrink-0" style="background-color: {{ $stg['color'] }};"></span>
                                <span>{{ $stg['position'] }}. {{ $stg['stage_name'] }} (Xác suất: {{ $stg['probability'] }}%)</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span>{{ $stg['opportunity_count'] }} Deal</span>
                                <strong class="text-indigo-600 dark:text-indigo-400">{{ number_format($stg['total_amount']) }} đ</strong>
                            </div>
                        </div>

                        <!-- Funnel Bar Container -->
                        <div class="flex items-center justify-center">
                            <div
                                class="h-10 rounded-xl transition-all duration-300 flex items-center justify-between px-4 text-xs font-bold text-white shadow-sm"
                                style="width: {{ $widthPercent }}%; background-color: {{ $stg['color'] }}; min-width: 220px;"
                            >
                                <span>{{ $stg['stage_name'] }}</span>
                                <span>{{ $stg['opportunity_count'] }} Deal</span>
                            </div>
                        </div>

                        <!-- Conversion Ratio Step Indicator -->
                        @if (! $loop->last)
                            <div class="flex items-center justify-center py-1">
                                <div class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 rounded-full">
                                    <span>↓ Tỷ lệ chuyển tiếp nấc kế:</span>
                                    <strong class="text-emerald-600 dark:text-emerald-400">{{ $funnel[$index + 1]['conversion_from_previous'] }}%</strong>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-12 text-center text-sm text-slate-400">
                Chưa có dữ liệu phễu chuyển đổi cho Quy trình đã chọn.
            </div>
        @endif
    </div>

    <!-- Detailed Funnel Data Table Card -->
    <div class="crm-card space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
            Bảng thống kê Chi tiết theo Giai đoạn
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-semibold text-slate-500 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3">STT</th>
                        <th class="px-4 py-3">Tên Giai đoạn (Stage)</th>
                        <th class="px-4 py-3 text-center">Xác suất (%)</th>
                        <th class="px-4 py-3 text-center">Số lượng Deal</th>
                        <th class="px-4 py-3 text-right">Tổng giá trị (đ)</th>
                        <th class="px-4 py-3 text-center">Chuyển tiếp Nấc trước (%)</th>
                        <th class="px-4 py-3 text-center">Chuyển đổi Tổng (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse ($this->funnelData as $row)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-bold">{{ $row['position'] }}</td>
                            <td class="px-4 py-3 font-semibold">
                                <div class="flex items-center gap-2">
                                    <span class="size-2.5 rounded-full" style="background-color: {{ $row['color'] }};"></span>
                                    <span>{{ $row['stage_name'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center font-mono">{{ $row['probability'] }}%</td>
                            <td class="px-4 py-3 text-center font-bold text-slate-900 dark:text-white">{{ $row['opportunity_count'] }}</td>
                            <td class="px-4 py-3 text-right font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($row['total_amount']) }} đ</td>
                            <td class="px-4 py-3 text-center font-semibold text-emerald-600 dark:text-emerald-400">{{ $row['conversion_from_previous'] }}%</td>
                            <td class="px-4 py-3 text-center font-semibold text-purple-600 dark:text-purple-400">{{ $row['conversion_from_top'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-400 italic">Không có dữ liệu giai đoạn.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
