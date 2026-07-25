<div>
    <!-- Modal Chi tiết Công việc, Checklist & Bình luận -->
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
            class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-6"
        >
            @if ($task)
                <!-- Header: Title & Badges -->
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                                {{ $task->title }}
                            </h2>
                            <flux:badge color="{{ $task->status->color() }}" size="sm">
                                {{ $task->status->label() }}
                            </flux:badge>
                            <flux:badge color="{{ $task->priority->color() }}" size="sm" variant="subtle">
                                {{ $task->priority->label() }}
                            </flux:badge>
                        </div>

                        <p class="text-xs text-slate-500">
                            Phân công: <strong>{{ $task->assignee?->name ?: 'Chưa phân công' }}</strong> •
                            Hạn chót: <strong>{{ $task->due_date ? $task->due_date->format('d/m/Y H:i') : 'Chưa thiết lập' }}</strong>
                        </p>
                    </div>

                    <flux:button wire:click="$set('showModal', false)" variant="ghost" size="sm" icon="x-mark" />
                </div>

                @if ($task->description)
                    <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700 dark:bg-slate-800/50 dark:text-slate-300">
                        {{ $task->description }}
                    </div>
                @endif

                <!-- Checklist Section -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                            <flux:icon.check-circle class="size-4 text-indigo-500" />
                            Danh sách kiểm tra (Checklist)
                        </h3>

                        @php
                            $totalChecklists = $task->checklists->count();
                            $completedChecklists = $task->checklists->where('is_completed', true)->count();
                            $percent = $totalChecklists > 0 ? round(($completedChecklists / $totalChecklists) * 100) : 0;
                        @endphp

                        @if ($totalChecklists > 0)
                            <span class="text-xs text-slate-500">{{ $completedChecklists }}/{{ $totalChecklists }} ({{ $percent }}%)</span>
                        @endif
                    </div>

                    @error('checklist_error')
                        <div class="text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror

                    <!-- Add Checklist Item -->
                    <form wire:submit.prevent="addChecklistItem" class="flex gap-2">
                        <flux:input
                            wire:model="newChecklistTitle"
                            placeholder="Thêm mục kiểm tra mới..."
                            size="sm"
                            class="flex-1"
                        />
                        <flux:button type="submit" variant="subtle" size="sm">Thêm</flux:button>
                    </form>

                    <!-- Checklist Items List -->
                    <div class="space-y-2">
                        @foreach ($task->checklists as $item)
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 p-2.5 dark:border-slate-800/60 dark:bg-slate-950/40">
                                <label class="flex items-center gap-2.5 cursor-pointer flex-1 text-sm">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleChecklistItem({{ $item->id }})"
                                        {{ $item->is_completed ? 'checked' : '' }}
                                        class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900"
                                    />
                                    <span class="{{ $item->is_completed ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-200' }}">
                                        {{ $item->title }}
                                    </span>
                                </label>

                                <flux:button wire:click="deleteChecklistItem({{ $item->id }})" size="sm" variant="ghost" icon="trash" class="text-slate-400 hover:text-red-500" />
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Attachment Files Section -->
                <div class="space-y-3 pt-2">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                        <flux:icon.paper-clip class="size-4 text-indigo-500" />
                        Tệp đính kèm
                    </h3>

                    <livewire:customers.customer-attachment-manager :modelType="App\Models\Task::class" :modelId="$task->id" />
                </div>

                <!-- Comments & Discussions Section -->
                <div class="space-y-4 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                        <flux:icon.chat-bubble-left-right class="size-4 text-indigo-500" />
                        Thảo luận & Bình luận
                    </h3>

                    @error('comment_error')
                        <div class="text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror

                    <!-- Add Comment Form -->
                    <form wire:submit.prevent="addComment" class="space-y-2">
                        <flux:textarea
                            wire:model="newCommentContent"
                            placeholder="Nhập ý kiến thảo luận..."
                            rows="2"
                        />
                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary" size="sm">Gửi bình luận</flux:button>
                        </div>
                    </form>

                    <!-- Comments List -->
                    <div class="space-y-3">
                        @foreach ($task->comments as $comment)
                            <div class="rounded-xl border border-slate-100 p-3 dark:border-slate-800/80 dark:bg-slate-950/40 space-y-1.5">
                                <div class="flex items-center justify-between text-xs text-slate-500">
                                    <span class="font-bold text-slate-900 dark:text-slate-200">{{ $comment->user?->name ?: 'Người dùng' }}</span>
                                    <div class="flex items-center gap-2">
                                        <span>{{ $comment->created_at?->diffForHumans() }}</span>
                                        @if (Auth::id() === $comment->user_id)
                                            <button type="button" wire:click="deleteComment({{ $comment->id }})" class="text-slate-400 hover:text-red-500">
                                                Xóa
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">
                                    {{ $comment->content }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
