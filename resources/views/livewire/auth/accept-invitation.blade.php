<div class="mx-auto w-full max-w-md space-y-6">
    <div class="text-center">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Kích hoạt tài khoản SalesFlow CRM</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            @if ($invitation)
                Chào mừng <strong>{{ $invitation->name }}</strong>! Vui lòng thiết lập mật khẩu đăng nhập.
            @else
                Xác thực thông tin lời mời tham gia hệ thống.
            @endif
        </p>
    </div>

    @if ($errorMessage)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-center dark:border-red-900 dark:bg-red-950/30">
            <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ $errorMessage }}</p>
            <div class="mt-4">
                <flux:button href="{{ route('login') }}" wire:navigate size="sm" variant="outline">Quay lại Đăng nhập</flux:button>
            </div>
        </div>
    @else
        <form wire:submit="accept" class="crm-card space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500">Email đăng nhập</label>
                <input type="text" value="{{ $invitation->email }}" disabled class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400" />
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500">Phòng ban & Vai trò</label>
                <input type="text" value="{{ $invitation->department?->name ?: 'Chưa gán' }} — Vai trò: {{ $invitation->role }}" disabled class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400" />
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase text-slate-500">Mật khẩu mới</label>
                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    placeholder="Mật khẩu tối thiểu 8 ký tự"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                />
                @error('password')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="passwordConfirmation" class="block text-xs font-semibold uppercase text-slate-500">Xác nhận mật khẩu</label>
                <input
                    id="passwordConfirmation"
                    type="password"
                    wire:model="passwordConfirmation"
                    placeholder="Nhập lại mật khẩu mới"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                />
            </div>

            <div class="pt-2">
                <flux:button type="submit" variant="primary" class="w-full">
                    Kích hoạt tài khoản & Đăng nhập
                </flux:button>
            </div>
        </form>
    @endif
</div>
