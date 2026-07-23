<div
    x-data="{ fullscreen: false }"
    x-on:keydown.escape.window="fullscreen = false"
    x-on:livewire:navigating.window="fullscreen = false"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', fullscreen)"
    @if (! $paused) wire:poll.2s="refreshLogs" @endif
>
    <div class="mb-8 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Vận hành</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">System Console</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Theo dõi operational log đã được làm sạch. Màn hình chỉ đọc và không thay thế nhật ký kiểm toán nghiệp vụ.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:badge :color="$paused ? 'amber' : 'green'">
                {{ $paused ? 'Đang tạm dừng' : 'Tự cập nhật mỗi 2 giây' }}
            </flux:badge>
            <flux:button wire:click="togglePolling" :icon="$paused ? 'play' : 'pause'">
                {{ $paused ? 'Tiếp tục' : 'Tạm dừng' }}
            </flux:button>
            <flux:button wire:click="refreshLogs" icon="arrow-path" variant="primary">Làm mới</flux:button>
        </div>
    </div>

    <section class="crm-card">
        <div class="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-12">
            <div class="md:col-span-2 xl:col-span-5">
                <flux:input
                    wire:model.live.debounce.500ms="search"
                    icon="magnifying-glass"
                    label="Tìm kiếm"
                    placeholder="Request ID, event, module, user hoặc HTTP status..."
                />
            </div>
            <div class="xl:col-span-3">
                <flux:select wire:model.live="level" label="Mức độ">
                    <option value="all">Tất cả mức độ</option>
                    @foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'] as $levelOption)
                        <option value="{{ $levelOption }}">{{ strtoupper($levelOption) }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="xl:col-span-2">
                <flux:select wire:model.live="module" label="Phân hệ">
                    <option value="all">Tất cả phân hệ</option>
                    @foreach ($modules as $moduleOption)
                        <option value="{{ $moduleOption }}">{{ str($moduleOption)->headline() }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="xl:col-span-2">
                <flux:select wire:model.live="limit" label="Số dòng">
                    @foreach ([50, 100, 200] as $limitOption)
                        <option value="{{ $limitOption }}">{{ $limitOption }} dòng</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="mb-4 flex flex-col justify-between gap-3 border-y border-slate-200 py-3 text-xs text-slate-500 dark:border-slate-800 sm:flex-row sm:items-center">
            <div class="flex flex-wrap items-center gap-2">
                <span>{{ count($entries) }} dòng phù hợp</span>
                <span aria-hidden="true">•</span>
                <span>Nguồn: {{ $source ?? 'Chưa có file JSON' }}</span>
                @if ($lastRefreshedAt)
                    <span aria-hidden="true">•</span>
                    <span>Cập nhật lúc {{ $lastRefreshedAt }} (UTC+7)</span>
                @endif
            </div>
            @if ($search !== '' || $level !== 'all' || $module !== 'all')
                <button type="button" wire:click="clearFilters" class="font-medium text-blue-600 hover:underline dark:text-blue-400">Xóa bộ lọc</button>
            @endif
        </div>

        <div class="relative" aria-live="polite">
            <div wire:loading.flex wire:target="refreshLogs,search,level,module,limit" class="absolute inset-x-0 top-0 z-10 items-center justify-center bg-white/80 py-3 text-sm text-slate-600 backdrop-blur-sm dark:bg-slate-900/80 dark:text-slate-300">
                Đang cập nhật nhật ký…
            </div>

            @if ($errorMessage)
                <div class="grid min-h-52 place-items-center rounded-xl border border-red-200 bg-red-50 text-center dark:border-red-900 dark:bg-red-950/30">
                    <div class="px-6">
                        <p class="font-medium text-red-700 dark:text-red-300">{{ $errorMessage }}</p>
                        <flux:button class="mt-4" wire:click="refreshLogs" icon="arrow-path">Thử lại</flux:button>
                    </div>
                </div>
            @elseif ($entries === [])
                <div class="grid min-h-52 place-items-center rounded-xl border border-dashed border-slate-300 text-center dark:border-slate-700">
                    <div class="px-6">
                        <p class="font-medium">Chưa có operational log phù hợp</p>
                        <p class="mt-1 text-sm text-slate-500">Thử xóa bộ lọc hoặc thực hiện một request mới trong hệ thống.</p>
                    </div>
                </div>
            @else
                <div
                    class="overflow-hidden rounded-xl border border-slate-800 bg-[#050b18] shadow-inner shadow-black/40"
                    :class="fullscreen ? 'fixed inset-0 z-50 flex h-screen flex-col rounded-none shadow-2xl sm:inset-4 sm:h-auto sm:rounded-xl' : ''"
                    x-bind:aria-label="fullscreen ? 'System Console toàn màn hình' : null"
                >
                    <div class="flex items-center justify-between border-b border-slate-800 bg-[#0a1428] px-4 py-3 font-mono text-xs text-slate-400">
                        <div class="flex items-center gap-3">
                            <span>salesflow://application</span>
                            <span class="hidden sm:inline">read-only · sanitized</span>
                        </div>
                        <button
                            type="button"
                            class="rounded-md border border-slate-700 px-3 py-1.5 font-sans font-medium text-slate-200 transition hover:border-slate-500 hover:bg-white/5"
                            x-on:click="fullscreen = ! fullscreen"
                            x-text="fullscreen ? 'Thu nhỏ (Esc)' : 'Toàn màn hình'"
                        >Toàn màn hình</button>
                    </div>
                    <ol
                        class="overflow-auto p-4 font-mono text-xs leading-6 sm:text-sm"
                        :class="fullscreen ? 'max-h-none flex-1' : 'max-h-[42rem]'"
                        reversed
                    >
                        @foreach ($entries as $entry)
                            @php
                                $levelClass = match ($entry['level']) {
                                    'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'text-red-400',
                                    'WARNING' => 'text-amber-300',
                                    'INFO', 'NOTICE' => 'text-sky-300',
                                    default => 'text-emerald-300',
                                };
                            @endphp
                            <li wire:key="system-log-{{ $entry['id'] }}" class="grid gap-x-3 border-l-2 border-transparent pl-2 hover:border-slate-500 hover:bg-white/[0.025] xl:grid-cols-[10.75rem_5.5rem_1fr]">
                                <span class="whitespace-nowrap text-blue-400">[{{ $entry['timestamp'] }}]</span>
                                <span class="{{ $levelClass }}">{{ str($entry['level'])->padRight(9) }}</span>
                                <span class="break-all text-slate-300">
                                    <span class="text-violet-300">{{ $entry['module'] }}/{{ $entry['action'] }}</span>
                                    <span class="text-slate-500">status={{ $entry['status_code'] ?? '—' }} user={{ $entry['user_id'] ?? 'guest' }} duration={{ $entry['duration_ms'] !== null ? $entry['duration_ms'].'ms' : '—' }}</span>
                                    <span class="text-amber-200">req={{ $entry['request_id'] }}</span>
                                    <span class="text-slate-200">{{ $entry['event'] }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </section>
</div>
