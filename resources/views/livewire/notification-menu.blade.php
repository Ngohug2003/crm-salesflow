<div class="relative" x-data="{ open: @entangle('open') }">
    <!-- Bell Button with Unread Badge -->
    <button
        type="button"
        @click="open = !open"
        class="relative flex size-9 items-center justify-center bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
        aria-label="Thông báo"
    >
        <flux:icon.bell class="size-5" />

        @if ($this->unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-rose-600 text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-slate-900">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Menu -->
    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 w-80 sm:w-96 rounded-2xl bg-white p-4 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-3"
    >
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                <span>Hộp thư thông báo</span>
                @if ($this->unreadCount > 0)
                    <flux:badge color="rose" size="sm">{{ $this->unreadCount }} chưa đọc</flux:badge>
                @endif
            </h3>

            @if ($this->unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                    Đánh dấu đã đọc tất cả
                </button>
            @endif
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800/60 max-h-80 overflow-y-auto">
            @forelse ($this->notifications as $n)
                @php
                    $isUnread = $n->read_at === null;
                    $data = $n->data;
                @endphp
                <div
                    wire:click="openNotification('{{ $n->id }}')"
                    class="py-3 flex items-start justify-between gap-3 cursor-pointer transition hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-lg px-2 {{ $isUnread ? 'bg-indigo-50/60 dark:bg-indigo-950/30' : '' }}"
                >
                    <div class="space-y-1 flex-1">
                        <p class="text-xs font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                            @if ($isUnread)
                                <span class="size-2 rounded-full bg-indigo-600 inline-block shrink-0"></span>
                            @endif
                            <span class="truncate">{{ $data['title'] ?? 'Thông báo hệ thống' }}</span>
                        </p>
                        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2">
                            {{ $data['message'] ?? '' }}
                        </p>
                        <div class="flex items-center gap-2 text-[10px] text-slate-400 pt-0.5">
                            <span>{{ $n->created_at?->diffForHumans() }}</span>
                            @if (isset($data['due_date']))
                                <span>• Hạn: {{ $data['due_date'] }}</span>
                            @endif
                        </div>
                    </div>

                    @if ($isUnread)
                        <button type="button" wire:click.stop="markAsRead('{{ $n->id }}')" title="Đánh dấu đã đọc" class="text-slate-400 hover:text-indigo-600">
                            <flux:icon.check class="size-4" />
                        </button>
                    @endif
                </div>
            @empty
                <div class="py-8 text-center text-xs text-slate-500">
                    Không có thông báo mới nào.
                </div>
            @endforelse
        </div>
    </div>
</div>
