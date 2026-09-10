<div class="w-full">
    @if ($errorMessage && ! $invitation)
        <div class="space-y-6 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                <flux:icon.exclamation-triangle class="size-7" />
            </div>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Không thể kích hoạt tài khoản</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $errorMessage }}</p>
            </div>
            <div class="pt-2">
                <flux:button href="{{ route('login') }}" wire:navigate variant="primary" class="w-full">
                    Về trang Đăng nhập
                </flux:button>
            </div>
        </div>
    @else
        <div>
            <p class="text-sm font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Tham gia workspace</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">Kích hoạt tài khoản</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Chào mừng <strong class="font-medium text-slate-800 dark:text-slate-200">{{ $invitation?->name }}</strong>! Vui lòng thiết lập mật khẩu để hoàn tất đăng ký tài khoản.
            </p>
        </div>

        @if ($invitation)
            <div class="mt-6 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
                <span class="inline-flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                    <flux:icon.envelope class="size-3.5 text-slate-400" />
                    {{ $invitation->email }}
                </span>
                <span class="text-slate-300 dark:text-slate-700">•</span>
                <span>Phòng ban: <strong>{{ $invitation->department?->name ?: 'Chưa phân bổ' }}</strong></span>
                <span class="text-slate-300 dark:text-slate-700">•</span>
                <flux:badge size="sm" color="emerald">{{ $invitation->role }}</flux:badge>
            </div>
        @endif

        <form wire:submit="accept" class="mt-6 space-y-5">
            <flux:input
                wire:model="password"
                type="password"
                label="Mật khẩu mới"
                placeholder="Tối thiểu 8 ký tự"
                required
                autocomplete="new-password"
                viewable
                autofocus
            />

            <flux:input
                wire:model="passwordConfirmation"
                type="password"
                label="Xác nhận mật khẩu"
                placeholder="Nhập lại mật khẩu mới"
                required
                autocomplete="new-password"
                viewable
            />

            @if ($errorMessage)
                <flux:callout variant="danger" heading="Không thể kích hoạt">
                    {{ $errorMessage }}
                </flux:callout>
            @endif

            <div class="pt-2">
                <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="accept">
                    Kích hoạt tài khoản & Đăng nhập
                </flux:button>
            </div>
        </form>

        <p class="mt-8 text-center text-xs text-slate-400 dark:text-slate-600">
            Bằng việc tiếp tục, bạn đồng ý với các chính sách vận hành bảo mật của SalesFlow CRM.
        </p>
    @endif
</div>
