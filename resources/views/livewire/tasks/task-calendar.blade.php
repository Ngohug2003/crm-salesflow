<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Lịch Công việc & Hoạt động</h1>
            <p class="mt-1 text-sm text-slate-500">Theo dõi các mốc công việc và hoạt động bán hàng theo Lịch tháng {{ $monthTitle }}.</p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button href="{{ route('tasks.index') }}" variant="subtle" icon="list-bullet" size="sm">
                Danh sách
            </flux:button>
            <flux:button href="{{ route('tasks.kanban') }}" variant="subtle" icon="view-columns" size="sm">
                Kanban
            </flux:button>
        </div>
    </div>

    <!-- Calendar Header & Navigation Controls -->
    <div class="crm-card">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Tháng {{ $monthTitle }}</h2>
                <flux:button wire:click="currentMonth" variant="ghost" size="sm">Tháng hiện tại</flux:button>
            </div>

            <div class="flex items-center gap-2">
                <flux:button wire:click="previousMonth" variant="subtle" icon="chevron-left" size="sm">Tháng trước</flux:button>
                <flux:button wire:click="nextMonth" variant="subtle" icon="chevron-right" size="sm">Tháng sau</flux:button>
            </div>
        </div>

        <!-- Month Grid View -->
        <div class="mt-6 grid grid-cols-7 gap-px rounded-xl bg-slate-200 dark:bg-slate-800 overflow-hidden text-xs">
            <!-- Day of Week Headers -->
            @foreach (['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'] as $dayName)
                <div class="bg-slate-100 p-2.5 text-center font-bold text-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    {{ $dayName }}
                </div>
            @endforeach

            <!-- Empty slots before month start -->
            @for ($i = 1; $i < $startOfWeekDay; $i++)
                <div class="min-h-[100px] bg-slate-50/50 p-2 dark:bg-slate-950/20"></div>
            @endfor

            <!-- Month Days -->
            @for ($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $dayEvents = $this->eventsByDate[$dateKey] ?? [];
                    $isToday = $dateKey === now()->format('Y-m-d');
                @endphp

                <div class="min-h-[110px] bg-white p-2 transition hover:bg-slate-50/80 dark:bg-slate-900 dark:hover:bg-slate-800/60 space-y-1.5 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="inline-grid size-6 place-items-center rounded-full font-bold {{ $isToday ? 'bg-indigo-600 text-white' : 'text-slate-700 dark:text-slate-300' }}">
                            {{ $day }}
                        </span>

                        @if (count($dayEvents) > 0)
                            <span class="text-[10px] font-semibold text-slate-400">{{ count($dayEvents) }} sự kiện</span>
                        @endif
                    </div>

                    <div class="space-y-1 overflow-y-auto max-h-[80px]">
                        @foreach ($dayEvents as $evt)
                            @if ($evt['type'] === 'task')
                                <div
                                    wire:click="$dispatch('open-task-detail', { taskId: {{ $evt['id'] }} })"
                                    class="cursor-pointer rounded px-1.5 py-1 text-[11px] font-medium bg-slate-100 text-slate-800 hover:bg-indigo-100 hover:text-indigo-900 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-indigo-950/80 truncate flex items-center justify-between gap-1"
                                >
                                    <span class="truncate">✓ {{ $evt['title'] }}</span>
                                    <span class="text-[9px] text-slate-400">{{ $evt['time'] }}</span>
                                </div>
                            @else
                                <div class="rounded px-1.5 py-1 text-[11px] font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 truncate flex items-center justify-between gap-1">
                                    <span class="truncate">📅 {{ $evt['title'] }}</span>
                                    <span class="text-[9px] text-indigo-400">{{ $evt['time'] }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <!-- Modal Xem Chi tiết Task -->
    <livewire:tasks.task-detail-modal />
</div>
