<div>
    <div class="mb-8 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <p class="text-sm text-slate-500">Cài đặt / Bảo mật</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Phiên đăng nhập</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Kiểm tra nơi tài khoản đang được sử dụng và thu hồi những phiên không còn tin cậy.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button wire:click="refreshSessions" icon="arrow-path">Làm mới</flux:button>
            <flux:button wire:click="confirmRevokeOthers" variant="danger" icon="trash">Thu hồi phiên khác</flux:button>
        </div>
    </div>

    <section class="crm-card">
        <div class="data-list-heading">
            <div>
                <h2 class="font-semibold">Danh sách phiên đăng nhập</h2>
                <p class="mt-1 text-sm text-slate-500">Có {{ count($sessions) }} phiên đang được ghi nhận cho tài khoản này.</p>
            </div>
        </div>

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

        <div class="data-list-content">
            <x-data-list.loading target="refreshSessions,revokeConfirmed,revokeOthersConfirmed" />

            @if ($sessions === [])
                <x-data-list.empty
                    title="Chưa có phiên đăng nhập"
                    description="Màn này cần SESSION_DRIVER=database để theo dõi và thu hồi phiên."
                    icon="computer-desktop"
                />
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Thiết bị</flux:table.column>
                        <flux:table.column>Vị trí mạng</flux:table.column>
                        <flux:table.column>Hoạt động gần nhất</flux:table.column>
                        <flux:table.column>Nhận diện</flux:table.column>
                        <flux:table.column align="end">Thao tác</flux:table.column>
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
                                <flux:table.cell align="end">
                                    <flux:button
                                        size="sm"
                                        variant="{{ $session['isCurrent'] ? 'danger' : 'ghost' }}"
                                        wire:click="confirmRevoke(@js($session['token']))"
                                    >
                                        {{ $session['isCurrent'] ? 'Đăng xuất phiên này' : 'Thu hồi' }}
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>
    </section>

    <!-- Modal Xác nhận Thu hồi Phiên cụ thể -->
    <div
        x-data="{ open: @entangle('confirmingRevokeToken') }"
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
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Xác nhận thu hồi phiên đăng nhập</h3>
                    <p class="text-xs text-slate-500">Phiên làm việc trên thiết bị này sẽ bị ngắt kết nối.</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bạn có chắc chắn muốn thu hồi phiên đăng nhập này không? Nếu thu hồi phiên hiện tại, bạn sẽ bị đăng xuất ngay lập tức.
            </p>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <flux:button wire:click="$set('confirmingRevokeToken', null)" variant="ghost" size="sm">
                    Hủy bỏ
                </flux:button>
                <flux:button wire:click="revokeConfirmed" variant="danger" size="sm">
                    Xác nhận thu hồi
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Modal Xác nhận Thu hồi Tất cả Phiên khác -->
    <div
        x-data="{ open: @entangle('confirmingRevokeOthers') }"
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
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Thu hồi tất cả phiên đăng nhập khác</h3>
                    <p class="text-xs text-slate-500">Đăng xuất tài khoản khỏi tất cả trình duyệt & thiết bị khác.</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bạn có chắc chắn muốn thu hồi toàn bộ phiên đăng nhập trên các trình duyệt khác ngoại trừ thiết bị hiện tại không?
            </p>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <flux:button wire:click="$set('confirmingRevokeOthers', false)" variant="ghost" size="sm">
                    Hủy bỏ
                </flux:button>
                <flux:button wire:click="revokeOthersConfirmed" variant="danger" size="sm">
                    Thu hồi tất cả
                </flux:button>
            </div>
        </div>
    </div>
</div>
