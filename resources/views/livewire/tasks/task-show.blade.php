<div class="space-y-6">
    <!-- Header / Navigation Bar -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('tasks.index') }}" variant="subtle" icon="arrow-left" size="sm">
                Quay lại danh sách
            </flux:button>

            @if ($task && $task->subject)
                <span class="text-xs text-slate-500">
                    Đối tượng liên quan:
                    <strong class="text-indigo-600 dark:text-indigo-400 font-semibold">
                        {{ class_basename($task->subject_type) }} #{{ $task->subject_id }}
                    </strong>
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <flux:button href="{{ route('tasks.kanban') }}" variant="subtle" icon="view-columns" size="sm">
                Kanban
            </flux:button>
            <flux:button href="{{ route('tasks.calendar') }}" variant="subtle" icon="calendar" size="sm">
                Lịch
            </flux:button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    @if ($task)
        <!-- Main 2-Column Responsive Layout -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Column: Task Info, Description, Checklist, Attachments (2 Cols) -->
            <div class="space-y-6 lg:col-span-2">
                <div class="crm-card space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                                    {{ $task->title }}
                                </h1>
                                <flux:badge color="{{ $task->status->color() }}" size="sm">
                                    {{ $task->status->label() }}
                                </flux:badge>
                                <flux:badge color="{{ $task->priority->color() }}" size="sm" variant="subtle">
                                    {{ $task->priority->label() }}
                                </flux:badge>
                            </div>

                            <p class="text-xs text-slate-500">
                                Đã tạo lúc: {{ $task->created_at?->format('d/m/Y H:i') }} bởi <strong>{{ $task->creator?->name }}</strong>
                            </p>
                        </div>
                    </div>

                    @if ($task->description)
                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                            <div class="task-rich-text-output text-sm text-slate-700 dark:text-slate-300">
                                <div class="ql-editor">{!! $task->description !!}</div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Checklist Section -->
                <div class="crm-card space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <flux:icon.check-circle class="size-5 text-indigo-500" />
                            Danh sách kiểm tra (Checklist)
                        </h3>

                        @php
                            $totalChecklists = $task->checklists->count();
                            $completedChecklists = $task->checklists->where('is_completed', true)->count();
                            $percent = $totalChecklists > 0 ? round(($completedChecklists / $totalChecklists) * 100) : 0;
                        @endphp

                        @if ($totalChecklists > 0)
                            <div class="flex items-center gap-3">
                                <div class="w-32 bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                    <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ $percent }}%</span>
                            </div>
                        @endif
                    </div>

                    @error('checklist_error')
                        <div class="text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror

                    @if ($canEdit)
                        <!-- Add Checklist Item -->
                        <form wire:submit.prevent="addChecklistItem" class="flex gap-2">
                            <flux:input
                                wire:model="newChecklistTitle"
                                placeholder="Thêm mục kiểm tra mới..."
                                size="sm"
                                class="flex-1"
                            />
                            <flux:button type="submit" variant="primary" size="sm">Thêm hạng mục</flux:button>
                        </form>
                    @endif

                    <!-- Checklist Items List -->
                    <div class="space-y-2 pt-1">
                        @forelse ($task->checklists as $item)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 p-3 dark:border-slate-800/80 dark:bg-slate-950/40 transition hover:border-slate-200">
                                <label class="flex items-center gap-3 {{ $canEdit ? 'cursor-pointer' : 'cursor-not-allowed' }} flex-1 text-sm">
                                    <input
                                        type="checkbox"
                                        @if ($canEdit) wire:click="toggleChecklistItem({{ $item->id }})" @else disabled @endif
                                        {{ $item->is_completed ? 'checked' : '' }}
                                        class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900"
                                    />
                                    <span class="{{ $item->is_completed ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-200 font-medium' }}">
                                        {{ $item->title }}
                                    </span>
                                </label>

                                @if ($canEdit)
                                    <flux:button wire:click="deleteChecklistItem({{ $item->id }})" size="sm" variant="ghost" icon="trash" class="text-slate-400 hover:text-red-500" />
                                @endif
                            </div>
                        @empty
                            <div class="py-4 text-center text-xs text-slate-400">
                                Chưa có mục kiểm tra nào.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Attachment Files Section -->
                <div class="crm-card space-y-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <flux:icon.paper-clip class="size-5 text-indigo-500" />
                        Tệp đính kèm tài liệu
                    </h3>

                    <livewire:customers.customer-attachment-manager :modelType="App\Models\Task::class" :modelId="$task->id" />
                </div>
            </div>

            <!-- Right Column: Task Settings & Live Discussion Feed (1 Col) -->
            <div class="space-y-6">
                <!-- Task Attributes Card -->
                <div class="crm-card space-y-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                        <span>Thuộc tính công việc</span>
                        @if (! $canEdit)
                            <span class="rounded bg-amber-100 dark:bg-amber-950/60 px-2 py-0.5 text-[10px] font-semibold text-amber-800 dark:text-amber-300">
                                Chế độ Xem (Read-only)
                            </span>
                        @endif
                    </h3>

                    @if (! $canEdit)
                        <p class="text-xs text-amber-600 dark:text-amber-400">
                            🔒 Bạn đang xem ở chế độ Đọc. Chỉ người tham gia Task mới có quyền chỉnh sửa thuộc tính & tiến độ.
                        </p>
                    @endif

                    <div class="space-y-4">
                        <flux:select wire:model.live="taskStatus" wire:change="updateTaskSettings" label="Trạng thái" :disabled="!$canEdit">
                            <option value="todo">Cần làm</option>
                            <option value="in_progress">Đang làm</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="cancelled">Đã hủy</option>
                        </flux:select>

                        <flux:select wire:model.live="taskPriority" wire:change="updateTaskSettings" label="Độ ưu tiên" :disabled="!$canEdit">
                            <option value="low">Thấp</option>
                            <option value="medium">Trung bình</option>
                            <option value="high">Cao</option>
                            <option value="urgent">Khẩn cấp</option>
                        </flux:select>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Người thực hiện chính</label>
                            <flux:select wire:model.live="assigneeId" wire:change="updateTaskSettings" :disabled="!$canEdit">
                                <option value="">Chưa phân công</option>
                                @foreach ($this->users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>

                        <!-- Multi-Assignees Display -->
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Thành viên cùng tham gia:</label>
                            @php
                                $allMembers = collect();
                                if ($task->assignee) $allMembers->push($task->assignee);
                                foreach ($task->assignees as $a) $allMembers->push($a);
                                $uniqueMembers = $allMembers->unique('id');
                            @endphp
                            @if ($uniqueMembers->count() > 0)
                                <div class="space-y-1.5">
                                    @foreach ($uniqueMembers as $member)
                                        <div class="flex items-center gap-2.5 rounded-lg border border-slate-100 dark:border-slate-800/60 px-3 py-2 bg-slate-50/60 dark:bg-slate-950/30">
                                            <span class="inline-flex items-center justify-center size-7 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-[10px] font-bold shrink-0 shadow-sm">
                                                {{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $member->name }}</p>
                                                <p class="text-[10px] text-slate-400 truncate">{{ $member->email }}</p>
                                            </div>
                                            @if ($member->id === $task->assigned_to)
                                                <span class="rounded bg-indigo-100 dark:bg-indigo-950/60 px-1.5 py-0.5 text-[9px] font-bold text-indigo-700 dark:text-indigo-300 shrink-0">Chính</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-slate-400 italic py-2">Chưa chọn thêm người tham gia</p>
                            @endif
                        </div>

                        <flux:input type="datetime-local" wire:model.live="dueDate" wire:change="updateTaskSettings" label="Hạn chót (Due date)" :disabled="!$canEdit" />

                        <flux:input type="datetime-local" wire:model.live="reminderDate" wire:change="updateTaskSettings" label="Thời gian Nhắc hạn 🔔" :disabled="!$canEdit" />
                    </div>
                </div>

                <!-- Discussion Feed Card with Clean List View UI -->
                <div
                    class="crm-card space-y-4"
                    x-data="{
                        showMentionDropdown: false,
                        users: {{ json_encode($this->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])) }},
                        query: '',
                        insertMention(name) {
                            let text = $wire.newCommentContent;
                            let lastAtPos = text.lastIndexOf('@');
                            if (lastAtPos !== -1) {
                                $wire.newCommentContent = text.substring(0, lastAtPos) + '@' + name + ' ';
                            } else {
                                $wire.newCommentContent += '@' + name + ' ';
                            }
                            this.showMentionDropdown = false;
                        },
                        handleInput(e) {
                            let text = e.target.value;
                            let lastAtPos = text.lastIndexOf('@');
                            if (lastAtPos !== -1 && lastAtPos >= text.length - 15) {
                                this.query = text.substring(lastAtPos + 1).toLowerCase();
                                this.showMentionDropdown = true;
                            } else {
                                this.showMentionDropdown = false;
                            }
                        }
                    }"
                >
                    <!-- Header -->
                    <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <flux:icon.chat-bubble-left-right class="size-5 text-indigo-500" />
                            Thảo luận thời gian thực
                        </span>
                        <span class="text-xs font-normal text-slate-500">{{ $task->comments->count() }} bình luận</span>
                    </h3>

                    @error('comment_error')
                        <div class="text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror

                    <!-- Comment List Feed (Chrono Order: Oldest at Top, Newest at Bottom) -->
                    <div class="space-y-3 max-h-[420px] overflow-y-auto pr-1">
                        @forelse ($task->comments as $comment)
                            @php
                                $senderName = $comment->user?->name ?: 'Người dùng';
                                $isMine = Auth::id() === $comment->user_id;
                            @endphp

                            <div class="rounded-xl border border-slate-100 p-3.5 dark:border-slate-800/80 dark:bg-slate-950/40 space-y-2">
                                <!-- Comment Header -->
                                <div class="flex items-center justify-between text-xs text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 dark:text-slate-200">{{ $senderName }}</span>
                                        <span class="text-[10px] text-slate-400">• {{ $comment->created_at?->diffForHumans() }}</span>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            wire:click="setReplyTo({{ $comment->id }})"
                                            class="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                                        >
                                            Trả lời
                                        </button>

                                        @if ($isMine)
                                            <button type="button" wire:click="deleteComment({{ $comment->id }})" class="text-xs text-slate-400 hover:text-red-500">
                                                Xóa
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Quoted Parent Comment if Replying -->
                                @if ($comment->parent)
                                    <div class="rounded-lg bg-slate-100/80 p-2 text-xs text-slate-600 dark:bg-slate-800/60 dark:text-slate-400 border-l-2 border-indigo-500">
                                        <strong class="font-semibold text-indigo-600 dark:text-indigo-400">@ {{ $comment->parent->user?->name }}:</strong>
                                        <span class="italic">"{{ mb_strimwidth($comment->parent->content, 0, 70, '...') }}"</span>
                                    </div>
                                @endif

                                <!-- Comment Content -->
                                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed">
                                    {{ $comment->content }}
                                </p>
                            </div>
                        @empty
                            <div class="py-8 text-center text-xs text-slate-400">
                                Chưa có thảo luận nào. Bắt đầu trao đổi ngay bên dưới.
                            </div>
                        @endforelse
                    </div>

                    <!-- Quoted Reply Banner -->
                    @if ($replyToCommentId)
                        <div class="flex items-center justify-between rounded-lg bg-indigo-50 px-3 py-2 text-xs text-indigo-900 dark:bg-indigo-950/60 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800">
                            <div class="truncate">
                                <span>Đang trả lời <strong>@ {{ $replyToUserName }}</strong>: </span>
                                <span class="italic font-medium">"{{ $replyToContentPreview }}"</span>
                            </div>
                            <button type="button" wire:click="cancelReply" class="text-slate-400 hover:text-red-500 ml-2 font-bold">
                                ✕
                            </button>
                        </div>
                    @endif

                    <!-- Add Comment Input Form with Smart Autocomplete Dropdown -->
                    <form wire:submit.prevent="addComment" class="space-y-2 relative pt-2 border-t border-slate-100 dark:border-slate-800">
                        <!-- Mention Autocomplete Dropdown Menu -->
                        <div
                            x-show="showMentionDropdown"
                            x-cloak
                            @click.outside="showMentionDropdown = false"
                            class="absolute bottom-full left-0 z-50 mb-1 w-64 rounded-xl bg-white p-2 shadow-xl border border-slate-200 dark:bg-slate-900 dark:border-slate-800 space-y-1 max-h-48 overflow-y-auto text-xs"
                        >
                            <div class="px-2 py-1 font-bold text-[10px] text-slate-400 uppercase tracking-wider">Tag thành viên:</div>
                            <template x-for="u in users.filter(usr => usr.name.toLowerCase().includes(query))" :key="u.id">
                                <button
                                    type="button"
                                    @click="insertMention(u.name)"
                                    class="w-full text-left rounded-lg px-2.5 py-1.5 font-medium text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-indigo-950/60 flex items-center justify-between"
                                >
                                    <span x-text="u.name"></span>
                                    <span class="text-[10px] text-indigo-500 font-semibold">@mention</span>
                                </button>
                            </template>
                        </div>

                        <flux:textarea
                            wire:model="newCommentContent"
                            @input="handleInput($event)"
                            placeholder="Nhập nội dung trao đổi... (gõ @ để nhắc tên thành viên)"
                            rows="3"
                        />
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-slate-400">Gõ <strong>@</strong> để gợi ý nhắc tên thành viên</span>
                            <flux:button type="submit" variant="primary" size="sm">Đăng thảo luận</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
