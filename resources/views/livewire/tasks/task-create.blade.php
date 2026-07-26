<div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tasks.index') }}" wire:navigate class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">
                    ← Công việc
                </a>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">Tạo Công việc mới</h1>
            <p class="mt-1 text-sm text-slate-500">Phân công nhiệm vụ, thiết lập tiến độ và thành viên tham gia.</p>
        </div>

        <div class="flex items-center gap-3">
            <flux:button href="{{ route('tasks.index') }}" wire:navigate variant="ghost" size="sm">
                Hủy bỏ
            </flux:button>
            <flux:button wire:click="saveTask" variant="primary" icon="plus" size="sm">
                Tạo Công việc
            </flux:button>
        </div>
    </div>

    @error('task_error')
        <div class="rounded-lg bg-red-50 p-4 text-sm font-semibold text-red-800 dark:bg-red-950/40 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    <!-- Main Form Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left 2-Columns: Main Details & Tiptap Editor -->
        <div class="lg:col-span-2 space-y-6">
            <div class="crm-card space-y-5">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                    Thông tin công việc
                </h3>

                <div class="space-y-4">
                    <flux:input wire:model="title" label="Tiêu đề công việc *" placeholder="Nhập tiêu đề công việc..." />
                    @error('title') <span class="text-xs text-red-500 block">{{ $message }}</span> @enderror

                    <x-rich-text-editor
                        model="description"
                        label="Mô tả chi tiết"
                        placeholder="Nhập chi tiết nội dung công việc, yêu cầu cần làm..."
                        editor-key="task-create-description"
                    />
                </div>
            </div>

            <!-- Subject Link Card -->
            <div class="crm-card space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                    Gắn với đối tượng bán hàng (Tùy chọn)
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:select wire:model.live="subjectType" label="Loại đối tượng">
                            <option value="">Không gắn (Công việc chung)</option>
                            <option value="App\Models\Opportunity">Cơ hội bán hàng (Deal)</option>
                            <option value="App\Models\Company">Doanh nghiệp (Company)</option>
                            <option value="App\Models\Lead">Khách hàng tiềm năng (Lead)</option>
                            <option value="App\Models\Contact">Người liên hệ (Contact)</option>
                        </flux:select>
                    </div>

                    @if ($subjectType === 'App\Models\Opportunity')
                        <div>
                            <flux:select wire:model="subjectId" label="Chọn Cơ hội (Deal)">
                                <option value="">Chọn Deal...</option>
                                @foreach ($this->opportunities as $op)
                                    <option value="{{ $op->id }}">{{ $op->title }} ({{ $op->code }})</option>
                                @endforeach
                            </flux:select>
                        </div>
                    @elseif ($subjectType === 'App\Models\Company')
                        <div>
                            <flux:select wire:model="subjectId" label="Chọn Doanh nghiệp">
                                <option value="">Chọn Doanh nghiệp...</option>
                                @foreach ($this->companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    @elseif ($subjectType === 'App\Models\Lead')
                        <div>
                            <flux:select wire:model="subjectId" label="Chọn Lead">
                                <option value="">Chọn Lead...</option>
                                @foreach ($this->leads as $l)
                                    <option value="{{ $l->id }}">{{ $l->full_name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    @elseif ($subjectType === 'App\Models\Contact')
                        <div>
                            <flux:select wire:model="subjectId" label="Chọn Người liên hệ">
                                <option value="">Chọn Contact...</option>
                                @foreach ($this->contacts as $ct)
                                    <option value="{{ $ct->id }}">{{ $ct->full_name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right 1-Column: Task Attributes & Assignees -->
        <div class="space-y-6">
            <div class="crm-card space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                    Thuộc tính & Phân công
                </h3>

                <div class="space-y-4">
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

                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Người thực hiện chính</label>
                        <flux:select wire:model="assigneeId">
                            <option value="">Chưa phân công</option>
                            @foreach ($this->users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <!-- Multi-Assignee Checkboxes -->
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Thành viên cùng tham gia</label>
                        <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/40 p-2.5 space-y-1.5">
                            @foreach ($this->users as $u)
                                <label class="flex items-center gap-2.5 cursor-pointer rounded-lg px-2 py-1.5 transition hover:bg-white dark:hover:bg-slate-800/60">
                                    <input
                                        type="checkbox"
                                        value="{{ $u->id }}"
                                        wire:model="assigneeIds"
                                        class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900"
                                    />
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center size-6 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-[9px] font-bold shrink-0 shadow-sm">
                                            {{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}
                                        </span>
                                        <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">{{ $u->name }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <flux:input type="datetime-local" wire:model="dueDate" label="Hạn chót (Due date)" />

                    <flux:input type="datetime-local" wire:model="reminderDate" label="Thời gian Nhắc hạn 🔔" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <flux:button href="{{ route('tasks.index') }}" wire:navigate variant="ghost" size="sm">
                    Hủy bỏ
                </flux:button>
                <flux:button wire:click="saveTask" variant="primary" icon="plus" size="sm">
                    Tạo Công việc
                </flux:button>
            </div>
        </div>
    </div>
</div>

@push('head')
@endpush
