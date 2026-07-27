<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Bảo mật tài khoản</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                {{ $isSelf ? 'Phiên đăng nhập của tôi' : 'Lịch sử phiên: '.$targetUser->name }}
            </h1>
            <p class="mt-2 max-w-3xl text-slate-500">Theo dõi các thiết bị, trình duyệt và địa chỉ IP đang đăng nhập tài khoản. Thu hồi ngay nếu phát hiện thiết bị lạ.</p>
        </div>

        @if ($isSelf && count($sessions) > 1)
            <flux:button wire:click="revokeOtherSessions" icon="power" variant="danger">
                Đăng xuất tất cả thiết bị khác
            </flux:button>
        @endif
    </div>

    @if ($feedbackMessage)
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-xs font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ $feedbackMessage }}
        </div>
    @endif

    <section class="crm-card">
        <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
            <h2 class="text-base font-semibold text-slate-950 dark:text-white">Danh sách thiết bị đang hoạt động (Active Sessions)</h2>
            <span class="text-xs text-slate-500">{{ count($sessions) }} phiên làm việc</span>
        </div>

        @if ($sessions === [])
            <div class="grid min-h-48 place-items-center rounded-xl border border-dashed border-slate-300 text-center dark:border-slate-700">
                <div class="px-6">
                    <p class="font-medium">Chưa có phiên làm việc active nào</p>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($sessions as $session)
                    <div wire:key="session-{{ $session['id'] }}" class="flex flex-col justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center">
                        <div class="flex items-start gap-4">
                            <div class="rounded-lg bg-slate-100 p-3 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                @if (str_contains(strtolower($session['platform']), 'android') || str_contains(strtolower($session['platform']), 'ios'))
                                    <flux:icon.device-phone-mobile class="size-6" />
                                @else
                                    <flux:icon.computer-desktop class="size-6" />
                                @endif
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ $session['browser'] }} trên {{ $session['platform'] }}</h3>
                                    @if ($session['is_current'])
                                        <flux:badge color="emerald" size="sm">Thiết bị này</flux:badge>
                                    @endif
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                    <span>IP: <code class="font-mono">{{ $session['ip_address'] }}</code></span>
                                    <span aria-hidden="true">•</span>
                                    <span>Tương tác gần nhất: {{ $session['last_activity_formatted'] }}</span>
                                </div>
                                <p class="mt-1 font-mono text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-xl" title="{{ $session['user_agent'] }}">
                                    {{ $session['user_agent'] }}
                                </p>
                            </div>
                        </div>

                        @if (! $session['is_current'])
                            <div class="flex items-center sm:self-center">
                                <flux:button wire:click="revokeSession('{{ $session['id'] }}')" size="sm" variant="ghost" class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/40">
                                    Thu hồi phiên
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
