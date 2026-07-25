<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Quản lý Công việc (Tasks)</h1>
            <p class="mt-1 text-sm text-slate-500">Theo dõi, phân công và xử lý các nhiệm vụ bán hàng.</p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button href="{{ route('tasks.kanban') }}" variant="subtle" icon="view-columns" size="sm">
                Xem dạng Kanban
            </flux:button>

            @can('create', App\Models\Task::class)
                <flux:button wire:click="openCreateModal" variant="primary" icon="plus" size="sm">
                    Tạo Công việc mới
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    @error('task_error')
        <div class="rounded-lg bg-red-50 p-4 text-sm font-semibold text-red-800 dark:bg-red-950/40 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    <div class="crm-card">
        <!-- Filter Bar -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="w-full sm:w-72">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Tìm theo tiêu đề, nội dung..."
                        icon="magnifying-glass"
                    />
                </div>

                <div class="w-full sm:w-44">
                    <flux:select wire:model.live="status" placeholder="Trạng thái">
                        <option value="">Tất cả trạng thái</option>
                        <option value="todo">Cần làm</option>
                        <option value="in_progress">Đang làm</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="cancelled">Đã hủy</option>
                    </flux:select>
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

        <!-- Task List Feed -->
        <div class="divide-y divide-slate-200 dark:divide-slate-800">
            @forelse ($this->tasks as $task)
                <div class="flex flex-col justify-between gap-4 py-4 sm:flex-row sm:items-center">
                    <div class="flex items-start gap-3">
                        @can('update', $task)
                            <input
                                type="checkbox"
                                wire:click="toggleTaskStatus({{ $task->id }})"
                                {{ $task->status->isFinished() ? 'checked' : '' }}
                                class="mt-1 size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900"
                            />
                        @endcan

                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-base font-semibold {{ $task->status->isFinished() ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-900 dark:text-white' }}">
                                    {{ $task->title }}
                                </span>

                                <flux:badge color="{{ $task->status->color() }}" size="sm">
                                    {{ $task->status->label() }}
                                </flux:badge>

                                <flux:badge color="{{ $task->priority->color() }}" size="sm" variant="subtle">
                                    {{ $task->priority->label() }}
                                </flux:badge>
                            </div>

                            @if ($task->description)
                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                    {{ $task->description }}
                                </p>
                            @endif

                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 pt-1">
                                <span>Phân công: <strong>{{ $task->assignee?->name ?: 'Chưa phân công' }}</strong></span>
                                <span>•</span>
                                <span>Tạo bởi: {{ $task->creator?->name ?: 'Hệ thống' }}</span>
                                @if ($task->due_date)
                                    <span>•</span>
                                    <span class="{{ $task->due_date->isPast() && !$task->status->isFinished() ? 'font-bold text-red-600 dark:text-red-400' : '' }}">
                                        Hạn chót: {{ $task->due_date->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                                @if ($task->subject)
                                    <span>•</span>
                                    <span class="text-indigo-600 dark:text-indigo-400 font-medium">
                                        Đối tượng: {{ class_basename($task->subject_type) }} #{{ $task->subject_id }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 self-end sm:self-center">
                        <flux:button wire:click="$dispatch('open-task-detail', { taskId: {{ $task->id }} })" size="sm" variant="subtle" icon="eye">
                            Chi tiết
                        </flux:button>

                        @can('update', $task)
                            <flux:button wire:click="openEditModal({{ $task->id }})" size="sm" variant="ghost" icon="pencil">
                                Sửa
                            </flux:button>
                        @endcan

                        @can('delete', $task)
                            <flux:button wire:click="confirmDeleteTask({{ $task->id }})" size="sm" variant="danger" icon="trash">
                                Xóa
                            </flux:button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-sm text-slate-500">
                    Chưa có công việc nào phù hợp với bộ lọc.
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $this->tasks->links() }}
        </div>
    </div>

    <!-- Modal Form Tạo / Sửa Công việc -->
    <div
        x-data="{ open: @entangle('showModal') }"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4"
        >
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                {{ $editingTaskId ? 'Chỉnh sửa Công việc' : 'Tạo Công việc mới' }}
            </h3>

            <div class="space-y-4">
                <flux:input wire:model="title" label="Tiêu đề công việc *" placeholder="Nhập tiêu đề công việc..." />

                <flux:textarea wire:model="description" label="Mô tả chi tiết" placeholder="Ghi chú thêm nội dung công việc..." rows="3" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="taskStatus" label="Trạng thái">
                        <option value="todo">Cần làm</option>
                        <option value="in_progress">Đang làm</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="cancelled">Đã hủy</option>
                    </flux:select>

                    <flux:select wire:model="taskPriority" label="Độ ưu tiên">
                        <option value="low">Thấp</option>
                        <option value="medium">Trung bình</option>
                        <option value="high">Cao</option>
                        <option value="urgent">Khẩn cấp</option>
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="datetime-local" wire:model="dueDate" label="Hạn chót (Due date)" />

                    <flux:select wire:model="assigneeId" label="Người thực hiện">
                        <option value="">Chưa phân công</option>
                        @foreach ($this->users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <flux:button wire:click="$set('showModal', false)" variant="ghost" size="sm">
                    Hủy bỏ
                </flux:button>
                <flux:button wire:click="saveTask" variant="primary" size="sm">
                    {{ $editingTaskId ? 'Cập nhật' : 'Tạo mới' }}
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Modal Xác nhận xóa Công việc -->
    <div
        x-data="{ open: @entangle('confirmingDeleteTaskId') }"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4"
        >
            <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
                <div class="rounded-full bg-red-100 p-2.5 dark:bg-red-950/60">
                    <flux:icon.exclamation-triangle class="size-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Xác nhận xóa Công việc</h3>
                    <p class="text-xs text-slate-500">Nhiệm vụ này sẽ được đưa vào thùng rác.</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bạn có chắc chắn muốn xóa công việc này không?
            </p>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <flux:button wire:click="$set('confirmingDeleteTaskId', null)" variant="ghost" size="sm">
                    Hủy bỏ
                </flux:button>
                <flux:button wire:click="deleteConfirmedTask" variant="danger" size="sm">
                    Xác nhận xóa
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Modal Xem Chi tiết Task (Checklist, Tệp đính kèm, Bình luận) -->
    <livewire:tasks.task-detail-modal />
</div>
