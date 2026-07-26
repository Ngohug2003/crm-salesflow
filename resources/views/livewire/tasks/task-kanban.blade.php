<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Công việc — Kanban Board</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý tiến độ công việc theo các cột trạng thái.</p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button href="{{ route('tasks.index') }}" wire:navigate variant="subtle" icon="list-bullet" size="sm">
                Xem dạng Danh sách
            </flux:button>
            <flux:button href="{{ route('tasks.calendar') }}" wire:navigate variant="subtle" icon="calendar" size="sm">
                Xem Lịch
            </flux:button>
            @can('create', App\Models\Task::class)
                <flux:button href="{{ route('tasks.create') }}" wire:navigate variant="primary" icon="plus" size="sm">
                    Tạo Công việc mới
                </flux:button>
            @endcan
        </div>
    </div>

    @error('kanban_error')
        <div class="rounded-lg bg-red-50 p-4 text-sm font-semibold text-red-800 dark:bg-red-950/40 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    <!-- Filter Bar -->
    <div class="crm-card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="w-full sm:w-72">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Tìm theo tiêu đề..."
                    icon="magnifying-glass"
                />
            </div>

            <div class="w-full sm:w-44">
                <flux:select wire:model.live="priority" placeholder="Độ ưu tiên">
                    <option value="">Tất cả độ ưu tiên</option>
                    <option value="low">Thấp</option>
                    <option value="medium">Trung bình</option>
                    <option value="high">Cao</option>
                    <option value="urgent">Khẩn cấp</option>
                </flux:select>
            </div>

            <div class="w-full sm:w-48">
                <flux:select wire:model.live="assignedTo" placeholder="Người thực hiện">
                    <option value="">Tất cả thành viên</option>
                    @foreach ($this->users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </div>

    <!-- Kanban Board Grid (4 Columns) -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        @php
            $columnConfigs = [
                'todo' => ['title' => 'Cần làm', 'color' => 'border-zinc-300 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950/40', 'badge' => 'zinc'],
                'in_progress' => ['title' => 'Đang làm', 'color' => 'border-blue-300 bg-blue-50/40 dark:border-blue-900/50 dark:bg-blue-950/30', 'badge' => 'blue'],
                'completed' => ['title' => 'Hoàn thành', 'color' => 'border-emerald-300 bg-emerald-50/40 dark:border-emerald-900/50 dark:bg-emerald-950/30', 'badge' => 'emerald'],
                'cancelled' => ['title' => 'Đã hủy', 'color' => 'border-rose-300 bg-rose-50/40 dark:border-rose-900/50 dark:bg-rose-950/30', 'badge' => 'rose'],
            ];
        @endphp

        @foreach ($columnConfigs as $statusKey => $cfg)
            @php
                $tasksInColumn = $this->columns[$statusKey] ?? new \Illuminate\Support\Collection();
            @endphp

            <div
                x-data
                @dragover.prevent
                @drop="
                    const taskId = event.dataTransfer.getData('text/plain');
                    if (taskId) {
                        $wire.moveTask(parseInt(taskId), '{{ $statusKey }}');
                    }
                "
                class="flex flex-col rounded-2xl border p-4 {{ $cfg['color'] }} space-y-3 min-h-[500px] transition-all hover:border-indigo-400"
            >
                <div class="flex items-center justify-between border-b pb-3 border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <span>{{ $cfg['title'] }}</span>
                        <flux:badge color="{{ $cfg['badge'] }}" size="sm">{{ $tasksInColumn->count() }}</flux:badge>
                    </h3>
                </div>

                <div class="flex-1 space-y-3">
                    @forelse ($tasksInColumn as $task)
                        <div
                            x-data
                            draggable="true"
                            @dragstart="event.dataTransfer.setData('text/plain', '{{ $task->id }}')"
                            class="group relative rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md hover:border-indigo-400 cursor-grab active:cursor-grabbing dark:border-slate-800 dark:bg-slate-900 space-y-2"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <span class="font-bold text-sm text-slate-900 dark:text-white line-clamp-2">
                                    {{ $task->title }}
                                </span>
                                <flux:badge color="{{ $task->priority->color() }}" size="sm" variant="subtle">
                                    {{ $task->priority->label() }}
                                </flux:badge>
                            </div>

                            @if ($task->description)
                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                    {{ str($task->description)->stripTags()->squish()->limit(120) }}
                                </p>
                            @endif

                            <div class="flex items-center justify-between text-[11px] text-slate-500 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                                <span>{{ $task->assignee?->name ?: 'Chưa phân công' }}</span>

                                @if ($task->due_date)
                                    <span class="{{ $task->due_date->isPast() && !$task->status->isFinished() ? 'font-bold text-red-500' : '' }}">
                                        {{ $task->due_date->format('d/m H:i') }}
                                    </span>
                                @endif
                            </div>

                            <!-- Move Column Dropdown & Details Action -->
                            <div class="flex items-center justify-between pt-2">
                                <flux:button href="{{ route('tasks.show', $task->id) }}" wire:navigate variant="ghost" size="sm" icon="eye">
                                    Chi tiết
                                </flux:button>

                                <div class="flex items-center gap-1">
                                    @foreach ($columnConfigs as $targetKey => $targetCfg)
                                        @if ($targetKey !== $statusKey)
                                            <button
                                                type="button"
                                                wire:click="moveTask({{ $task->id }}, '{{ $targetKey }}')"
                                                title="Chuyển sang {{ $targetCfg['title'] }}"
                                                class="rounded px-1.5 py-0.5 text-[10px] font-semibold transition bg-slate-100 text-slate-700 hover:bg-indigo-100 hover:text-indigo-700 dark:bg-slate-800 dark:text-slate-300"
                                            >
                                                → {{ $targetCfg['title'] }}
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="grid min-h-[120px] place-items-center rounded-xl border border-dashed border-slate-200 text-xs text-slate-400 dark:border-slate-800">
                            Thả công việc vào đây
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal Xem Chi tiết Task -->
    <livewire:tasks.task-detail-modal />
</div>
