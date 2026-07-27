<div class="space-y-6">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Thông báo</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Hộp thư thông báo hệ thống</h1>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Quản lý và theo dõi toàn bộ nhắc nhở nhiệm vụ, kết quả tác vụ Import/Export và thông báo hệ thống.</p>
        </div>

        <div class="flex items-center gap-3">
            @if ($this->unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 focus:outline-hidden"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Đánh dấu tất cả đã đọc ({{ $this->unreadCount }})
                </button>
            @endif

            <button
                type="button"
                wire:click="deleteAllRead"
                wire:confirm="Bạn có chắc chắn muốn xóa tất cả các thông báo đã đọc không?"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
            >
                <svg class="size-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Xóa tin đã đọc
            </button>

            <flux:button :href="route('notifications.settings')" wire:navigate variant="outline" size="sm" icon="cog-6-tooth">Cài đặt thông báo</flux:button>
        </div>
    </div>

    {{-- Filter Bar & Search --}}
    <div class="crm-card space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            {{-- Tabs Filter --}}
            <div class="flex rounded-lg bg-slate-100 p-1 dark:bg-slate-800 self-start">
                <button
                    type="button"
                    wire:click="$set('filter', 'all')"
                    class="rounded-md px-3 py-1.5 text-xs font-bold transition-colors {{ $filter === 'all' ? 'bg-white text-indigo-600 shadow-xs dark:bg-slate-900 dark:text-indigo-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
                >
                    Tất cả ({{ $this->totalCount }})
                </button>
                <button
                    type="button"
                    wire:click="$set('filter', 'unread')"
                    class="rounded-md px-3 py-1.5 text-xs font-bold transition-colors {{ $filter === 'unread' ? 'bg-white text-indigo-600 shadow-xs dark:bg-slate-900 dark:text-indigo-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
                >
                    Chưa đọc ({{ $this->unreadCount }})
                </button>
                <button
                    type="button"
                    wire:click="$set('filter', 'read')"
                    class="rounded-md px-3 py-1.5 text-xs font-bold transition-colors {{ $filter === 'read' ? 'bg-white text-indigo-600 shadow-xs dark:bg-slate-900 dark:text-indigo-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
                >
                    Đã đọc
                </button>
            </div>

            {{-- Search Box --}}
            <div class="w-full sm:w-72">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Tìm kiếm nội dung thông báo..."
                        class="w-full rounded-lg border border-slate-300 bg-white py-1.5 pl-9 pr-3 text-xs text-slate-900 focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    >
                    <svg class="size-4 absolute left-2.5 top-2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Notifications List --}}
        <div class="divide-y divide-slate-200 dark:divide-slate-800 border-t border-slate-200 dark:border-slate-800 pt-2">
            @forelse ($this->notificationsList as $notification)
                @php
                    $isUnread = $notification->read_at === null;
                    $data = $notification->data;
                    $type = $data['type'] ?? 'system';
                @endphp

                <div class="group flex items-start justify-between gap-4 py-4 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40 rounded-lg px-3 {{ $isUnread ? 'bg-indigo-50/40 dark:bg-indigo-950/20' : '' }}">
                    <div class="flex items-start gap-3 flex-1 cursor-pointer" wire:click="openNotification('{{ $notification->id }}')">
                        {{-- Icon according to notification type --}}
                        <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg {{ $isUnread ? 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/80 dark:text-indigo-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                            @if (str_contains($type, 'import'))
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                            @elseif (str_contains($type, 'export'))
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            @elseif (str_contains($type, 'task') || str_contains($type, 'reminder'))
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            @else
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 0 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 0-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                @if ($isUnread)
                                    <span class="size-2 rounded-full bg-indigo-600 inline-block"></span>
                                @endif
                                <h3 class="text-xs font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                    {{ $data['title'] ?? 'Thông báo hệ thống' }}
                                </h3>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-300">
                                {{ $data['message'] ?? '' }}
                            </p>
                            <div class="flex items-center gap-3 text-[11px] text-slate-400 pt-1">
                                <span>{{ $notification->created_at?->diffForHumans() }}</span>
                                <span>•</span>
                                <span>{{ $notification->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                        @if ($isUnread)
                            <button
                                type="button"
                                wire:click="markAsRead('{{ $notification->id }}')"
                                title="Đánh dấu đã đọc"
                                class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800 dark:hover:text-indigo-400"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </button>
                        @endif

                        <button
                            type="button"
                            wire:click="deleteNotification('{{ $notification->id }}')"
                            title="Xóa thông báo"
                            class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 0 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 0-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </div>
                    <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">Không tìm thấy thông báo nào</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Hộp thư của bạn hiện tại chưa có thông báo mới hoặc không khớp với từ khóa tìm kiếm.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
            {{ $this->notificationsList->links() }}
        </div>
    </div>
</div>
