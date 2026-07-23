<div>
    <div class="mb-8 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Bảo mật</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Phiên đăng nhập</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Kiểm tra nơi tài khoản đang được sử dụng và thu hồi những phiên không còn tin cậy.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button wire:click="refreshSessions" icon="arrow-path">Làm mới</flux:button>
            <flux:button wire:click="revokeOthers" variant="danger" icon="trash">Thu hồi phiên khác</flux:button>
        </div>
    </div>

    <section class="crm-card">
        @if ($statusMessage)
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
                {{ $statusMessage }}
            </div>
        @endif

        @error('session')
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">
                {{ $message }}
            </div>
        @enderror

        @if ($sessions === [])
            <div class="grid min-h-52 place-items-center rounded-xl border border-dashed border-slate-300 text-center dark:border-slate-700">
                <div class="px-6">
                    <p class="font-medium">Chưa có phiên đăng nhập nào trong bảng session</p>
                    <p class="mt-1 text-sm text-slate-500">Màn này cần `SESSION_DRIVER=database` để theo dõi và thu hồi phiên.</p>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Thiết bị</flux:table.column>
                        <flux:table.column>Vị trí mạng</flux:table.column>
                        <flux:table.column>Hoạt động gần nhất</flux:table.column>
                        <flux:table.column>Nhận diện</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($sessions as $session)
                            <flux:table.row :key="$session['fingerprint']">
                                <flux:table.cell>
                                    <div class="min-w-52">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ $session['browser'] }}</span>
                                            @if ($session['isCurrent'])
                                                <flux:badge size="sm" color="green">Phiên hiện tại</flux:badge>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">{{ $session['device'] }} · {{ $session['platform'] }}</p>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="font-mono text-sm">{{ $session['ipAddress'] }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="whitespace-nowrap">{{ $session['lastActiveAt'] }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="font-mono text-xs text-slate-500">#{{ $session['fingerprint'] }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button
                                        size="sm"
                                        variant="{{ $session['isCurrent'] ? 'danger' : 'ghost' }}"
                                        wire:click="revoke(@js($session['token']))"
                                        wire:confirm="{{ $session['isCurrent'] ? 'Thu hồi phiên hiện tại sẽ đăng xuất bạn ngay. Tiếp tục?' : 'Thu hồi phiên đăng nhập này?' }}"
                                    >
                                        {{ $session['isCurrent'] ? 'Đăng xuất phiên này' : 'Thu hồi' }}
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </section>
</div>
