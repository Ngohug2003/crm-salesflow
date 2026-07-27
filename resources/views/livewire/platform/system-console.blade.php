<div
    x-data="{
        fullscreen: false,
        realtimeStatus: 'connecting',
        newCount: 0,
        echoChannel: null,
        filterLevel: @js(strtoupper($level)),
        filterModule: @js($module),
        filterSearch: @js($search),
        filterLimit: @js($limit),

        matchesFilters(entry) {
            if (this.filterLevel !== 'ALL' && entry.level !== this.filterLevel) return false;
            if (this.filterModule !== 'all' && entry.module !== this.filterModule) return false;
            if (this.filterSearch.trim() !== '') {
                const q = this.filterSearch.toLowerCase();
                if (!entry.line.toLowerCase().includes(q)) return false;
            }
            return true;
        },

        initEcho() {
            if (!window.Echo) return;

            this.echoChannel = window.Echo.private('system-console')
                .listen('.SystemLogEntryCreated', (data) => {
                    const entry = data.entry;
                    if (!entry || !this.matchesFilters(entry)) return;

                    // Prepend new entry to Livewire component entries via $wire
                    this.$wire.entries.unshift(entry);
                    if (this.$wire.entries.length > this.filterLimit) {
                        this.$wire.entries.splice(this.filterLimit);
                    }
                    this.newCount++;
                    this.$wire.set('lastRefreshedAt', new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
                })
                .subscribed(() => { this.realtimeStatus = 'connected'; })
                .error(() => { this.realtimeStatus = 'error'; });

            // Also track Echo connection state changes
            if (window.Echo?.connector?.pusher?.connection) {
                window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
                    if (current === 'connected') this.realtimeStatus = 'connected';
                    else if (current === 'disconnected' || current === 'failed') this.realtimeStatus = 'disconnected';
                    else this.realtimeStatus = 'connecting';
                });
            }
        },

        resetNewCount() { this.newCount = 0; },
    }"
    x-init="initEcho()"
    x-on:keydown.escape.window="fullscreen = false"
    x-on:livewire:navigating.window="fullscreen = false; echoChannel && window.Echo.leave('system-console')"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', fullscreen)"
>
    <div class="mb-8 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Vận hành</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">System Console</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Theo dõi operational log đã được làm sạch. Màn hình chỉ đọc và không thay thế nhật ký kiểm toán nghiệp vụ.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Realtime connection status badge --}}
            <span
                x-show="realtimeStatus === 'connected'"
                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
            >
                <span class="relative flex size-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                </span>
                Realtime
                <span x-show="newCount > 0" class="ml-0.5 rounded-full bg-emerald-200 px-1.5 py-0.5 text-[10px] text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200" x-text="'+' + newCount + ' mới'"></span>
            </span>
            <span
                x-show="realtimeStatus === 'connecting'"
                class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
            >
                <span class="size-2 animate-pulse rounded-full bg-amber-400"></span>
                Đang kết nối...
            </span>
            <span
                x-show="realtimeStatus === 'disconnected' || realtimeStatus === 'error'"
                class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-950/50 dark:text-red-300"
            >
                <span class="size-2 rounded-full bg-red-400"></span>
                Mất kết nối
            </span>

            <flux:button wire:click="refreshLogs" x-on:click="resetNewCount()" icon="arrow-path" variant="primary">Làm mới log</flux:button>
        </div>
    </div>

    @if ($healthReport)
        <section class="crm-card mb-8" aria-labelledby="health-check-title">
            <div class="flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 id="health-check-title" class="text-base font-semibold text-slate-950 dark:text-white">Kiểm tra sức khỏe hệ thống</h2>
                        @php
                            $badgeColor = match ($healthReport['status']) {
                                'ok' => 'emerald',
                                'warning' => 'amber',
                                default => 'red',
                            };
                            $badgeText = match ($healthReport['status']) {
                                'ok' => 'Tất cả dịch vụ OK',
                                'warning' => 'Có cảnh báo',
                                default => 'Phát hiện lỗi dịch vụ',
                            };
                        @endphp
                        <flux:badge :color="$badgeColor" size="sm">{{ $badgeText }}</flux:badge>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Kiểm tra trực tiếp kết nối Database, Redis, Queue, Realtime, Storage và Runtime.</p>
                </div>
                <flux:button wire:click="refreshHealthCheck" icon="arrow-path" variant="ghost" size="sm">Kiểm tra lại</flux:button>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @php $svc = $healthReport['services']; @endphp

                {{-- 1. Database --}}
                <div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">1. PostgreSQL Database</span>
                        <flux:badge :color="$svc['database']['status'] === 'ok' ? 'emerald' : ($svc['database']['status'] === 'warning' ? 'amber' : 'red')" size="sm">
                            {{ strtoupper($svc['database']['status']) }}
                        </flux:badge>
                    </div>
                    <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ $svc['database']['latency_ms'] }} ms</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $svc['database']['details'] }}</p>
                </div>

                {{-- 2. Redis --}}
                <div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">2. Redis Cache</span>
                        <flux:badge :color="$svc['redis']['status'] === 'ok' ? 'emerald' : ($svc['redis']['status'] === 'warning' ? 'amber' : 'red')" size="sm">
                            {{ strtoupper($svc['redis']['status']) }}
                        </flux:badge>
                    </div>
                    <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ $svc['redis']['latency_ms'] }} ms</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $svc['redis']['details'] }}</p>
                </div>

                {{-- 3. Queue Workers --}}
                <div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">3. Queue Workers</span>
                        <flux:badge :color="$svc['queue']['status'] === 'ok' ? 'emerald' : 'amber'" size="sm">
                            {{ strtoupper($svc['queue']['status']) }}
                        </flux:badge>
                    </div>
                    <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ $svc['queue']['pending_jobs'] }} chờ / {{ $svc['queue']['failed_jobs'] }} lỗi</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $svc['queue']['details'] }}</p>
                </div>

                {{-- 4. Reverb Realtime --}}
                <div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">4. Reverb WebSocket</span>
                        <flux:badge color="emerald" size="sm">OK</flux:badge>
                    </div>
                    <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ $svc['realtime']['host'] }}:{{ $svc['realtime']['port'] }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $svc['realtime']['details'] }}</p>
                </div>

                {{-- 5. Storage Disks --}}
                <div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">5. Storage Disks</span>
                        <flux:badge :color="$svc['storage']['status'] === 'ok' ? 'emerald' : 'red'" size="sm">
                            {{ strtoupper($svc['storage']['status']) }}
                        </flux:badge>
                    </div>
                    <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">Local & Public</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $svc['storage']['details'] }}</p>
                </div>

                {{-- 6. Application Environment --}}
                <div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">6. Runtime Environment</span>
                        <flux:badge color="emerald" size="sm">PHP {{ $svc['app']['php_version'] }}</flux:badge>
                    </div>
                    <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">Laravel {{ $svc['app']['laravel_version'] }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $svc['app']['details'] }}</p>
                </div>
            </div>
        </section>
    @endif

    @if ($envChecklist)
        <section class="crm-card mb-8" aria-labelledby="env-checklist-title">
            <div class="flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 id="env-checklist-title" class="text-base font-semibold text-slate-950 dark:text-white">Environment Readiness Checklist</h2>
                        @php
                            $envBadgeColor = match ($envChecklist['overall_status']) {
                                'passed' => 'emerald',
                                'warning' => 'amber',
                                default => 'red',
                            };
                            $envBadgeText = match ($envChecklist['overall_status']) {
                                'passed' => 'Sẵn sàng Deployment',
                                'warning' => 'Cần lưu ý trước deploy',
                                default => 'Chưa đủ điều kiện deploy',
                            };
                        @endphp
                        <flux:badge :color="$envBadgeColor" size="sm">{{ $envBadgeText }}</flux:badge>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Kiểm tra nhanh biến môi trường, bảo mật cookie, symlink và cấu hình dịch vụ trước khi đưa ứng dụng lên Staging/Production.</p>
                </div>
                <flux:button wire:click="refreshEnvChecklist" icon="arrow-path" variant="ghost" size="sm">Kiểm tra lại</flux:button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-950/50 dark:text-slate-400">
                        <tr>
                            <th class="py-2.5 pl-3 pr-2 font-medium">Phân loại</th>
                            <th class="px-2 py-2.5 font-medium">Mục kiểm tra</th>
                            <th class="px-2 py-2.5 font-medium">Trạng thái</th>
                            <th class="px-2 py-2.5 font-medium">Tóm tắt cấu hình</th>
                            <th class="py-2.5 pl-2 pr-3 font-medium">Khuyến nghị</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($envChecklist['items'] as $item)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50">
                                <td class="py-2.5 pl-3 pr-2 font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $item['category'] }}</td>
                                <td class="px-2 py-2.5 font-semibold text-slate-900 dark:text-white whitespace-nowrap">{{ $item['title'] }}</td>
                                <td class="px-2 py-2.5 whitespace-nowrap">
                                    <flux:badge :color="$item['status'] === 'passed' ? 'emerald' : ($item['status'] === 'warning' ? 'amber' : 'red')" size="sm">
                                        {{ $item['status'] === 'passed' ? '✓ ĐẠT' : ($item['status'] === 'warning' ? '⚠ CẢNH BÁO' : '✗ CHƯA ĐẠT') }}
                                    </flux:badge>
                                </td>
                                <td class="px-2 py-2.5 text-slate-700 dark:text-slate-300">{{ $item['summary'] }}</td>
                                <td class="py-2.5 pl-2 pr-3 text-slate-500 dark:text-slate-400">{{ $item['recommendation'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

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
