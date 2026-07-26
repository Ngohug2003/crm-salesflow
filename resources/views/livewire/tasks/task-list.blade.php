<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Công việc</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Công việc</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Theo dõi, phân công và xử lý các nhiệm vụ bán hàng.</p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('tasks.kanban')" wire:navigate variant="ghost" icon="view-columns">
                Kanban
            </flux:button>
            <flux:button :href="route('tasks.calendar')" wire:navigate variant="ghost" icon="calendar">
                Lịch
            </flux:button>

            @can('create', App\Models\Task::class)
                <flux:button :href="route('tasks.create')" wire:navigate variant="primary" icon="plus">
                    Tạo công việc
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('message') }}
        </div>
    @endif

    @error('task_error')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">
            {{ $message }}
        </div>
    @enderror

    <div class="crm-card relative">
        <div class="data-list-heading">
            <div>
                <h2 class="font-semibold">Danh sách công việc</h2>
                <p class="mt-1 text-sm text-slate-500">Có {{ $this->tasks->total() }} công việc phù hợp trong phạm vi.</p>
            </div>
            @if ($search !== '' || $status !== '' || $priority !== '' || $assignedTo !== '')
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Xóa bộ lọc</flux:button>
            @endif
        </div>

        <div class="data-list-filters mb-5">
            <flux:input
                wire:model.live.debounce.300ms="search"
                label="Tìm kiếm"
                placeholder="Tiêu đề hoặc nội dung..."
                icon="magnifying-glass"
            />

            <flux:select wire:model.live="status" label="Trạng thái">
                <option value="">Tất cả trạng thái</option>
                <option value="todo">Cần làm</option>
                <option value="in_progress">Đang làm</option>
                <option value="completed">Hoàn thành</option>
                <option value="cancelled">Đã hủy</option>
            </flux:select>

            <flux:select wire:model.live="priority" label="Độ ưu tiên">
                <option value="">Tất cả độ ưu tiên</option>
                <option value="low">Thấp</option>
                <option value="medium">Trung bình</option>
                <option value="high">Cao</option>
                <option value="urgent">Khẩn cấp</option>
            </flux:select>

            <flux:select wire:model.live="assignedTo" label="Người thực hiện">
                <option value="">Tất cả thành viên</option>
                @foreach ($this->users as $userOption)
                    <option value="{{ $userOption->id }}">{{ $userOption->name }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="data-list-content">
            <x-data-list.loading target="search,status,priority,assignedTo,clearFilters,gotoPage,nextPage,previousPage" />

            @if ($this->tasks->isEmpty())
                <x-data-list.empty
                    title="Không tìm thấy công việc"
                    description="Thử thay đổi từ khóa hoặc các bộ lọc hiện tại."
                    icon="clipboard-document-list"
                >
                    @if ($search !== '' || $status !== '' || $priority !== '' || $assignedTo !== '')
                        <x-slot:action>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Đặt lại bộ lọc</flux:button>
                        </x-slot:action>
                    @endif
                </x-data-list.empty>
            @else
                <div class="data-list-feed">
                    @foreach ($this->tasks as $task)
                        <div class="data-list-feed-item">
                    <div class="flex items-start gap-3">
                        @can('update', $task)
                            <input
                                type="checkbox"
                                wire:click="toggleTaskStatus({{ $task->id }})"
                                {{ $task->status->isFinished() ? 'checked' : '' }}
                                class="mt-1 size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900"
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
                                    {{ str($task->description)->stripTags()->squish()->limit(100) }}
                                </p>
                            @endif

                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 pt-1">
                                <div class="flex items-center gap-1.5">
                                    <span>Phân công:</span>
                                    @if ($task->assignees->count() > 0 || $task->assignee)
                                        <div class="flex items-center -space-x-2">
                                            @php
                                                $allMembers = collect();
                                                if ($task->assignee) $allMembers->push($task->assignee);
                                                foreach ($task->assignees as $a) $allMembers->push($a);
                                                $uniqueMembers = $allMembers->unique('id');
                                            @endphp
                                            @foreach ($uniqueMembers->take(5) as $member)
                                                <span
                                                    title="{{ $member->name }}"
                                                    class="inline-flex items-center justify-center size-6 rounded-full border-2 border-white dark:border-slate-900 bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-[9px] font-bold shadow-sm"
                                                >
                                                    {{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}
                                                </span>
                                            @endforeach
                                            @if ($uniqueMembers->count() > 5)
                                                <span class="inline-flex items-center justify-center size-6 rounded-full border-2 border-white dark:border-slate-900 bg-slate-200 dark:bg-slate-700 text-[9px] font-bold text-slate-600 dark:text-slate-300">
                                                    +{{ $uniqueMembers->count() - 5 }}
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-slate-400">{{ $uniqueMembers->pluck('name')->implode(', ') }}</span>
                                    @else
                                        <span class="text-slate-400 italic">Chưa phân công</span>
                                    @endif
                                </div>
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
                        <flux:button href="{{ route('tasks.show', $task->id) }}" wire:navigate size="sm" variant="subtle" icon="eye">
                            Chi tiết
                        </flux:button>

                        @can('update', $task)
                            <flux:button href="{{ route('tasks.show', $task->id) }}" wire:navigate size="sm" variant="ghost" icon="pencil">
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
                    @endforeach
                </div>

                <x-data-list.pagination :paginator="$this->tasks" />
            @endif
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

                <x-rich-text-editor
                    model="description"
                    label="Mô tả chi tiết"
                    placeholder="Nhập nội dung mô tả công việc chi tiết..."
                    editor-key="task-list-description-{{ $editingTaskId ?? 'create' }}"
                />

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

                    <flux:input type="datetime-local" wire:model="reminderDate" label="Thời gian Nhắc hạn" />
                </div>

                <div class="w-full">
                    <flux:select wire:model="assigneeId" label="Người thực hiện chính">
                        <option value="">Chưa phân công</option>
                        @foreach ($this->users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Multi-Assignee Checkboxes -->
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Thành viên cùng tham gia</label>
                    <div class="max-h-40 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/40 p-3 space-y-2">
                        @foreach ($this->users as $u)
                            <label class="flex items-center gap-3 cursor-pointer rounded-lg px-2 py-1.5 transition hover:bg-white dark:hover:bg-slate-800/60">
                                <input
                                    type="checkbox"
                                    value="{{ $u->id }}"
                                    wire:model="assigneeIds"
                                    class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900"
                                />
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center size-7 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-[10px] font-bold shrink-0">
                                        {{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}
                                    </span>
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ $u->name }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
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

@push('head')
@endpush
