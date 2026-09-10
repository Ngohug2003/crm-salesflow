<x-layouts.guest title="Đăng nhập — SalesFlow CRM">
    <!-- <div class="mb-8 lg:hidden"><span class="grid size-10 place-items-center rounded-xl bg-emerald-400 font-black text-slate-950">SF</span></div> -->
    <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">WELCOME BACK</p>
    <h1 class="mt-2 text-3xl font-semibold">Đăng nhập SalesFlow</h1>
    <p class="mt-2 text-sm text-slate-500">Dùng tài khoản được quản trị viên cấp.</p>
    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">@csrf
        <flux:input name="email" type="email" label="Email" value="{{ old('email') }}" required autofocus autocomplete="username" />
        <flux:input
            name="password"
            type="password"
            label="Mật khẩu"
            value="{{ app()->isLocal() ? config('crm.local_login_password') : '' }}"
            required
            autocomplete="current-password"
            viewable
        />
        <div class="flex items-center justify-between"><flux:checkbox name="remember" label="Ghi nhớ đăng nhập" /><a class="text-sm font-medium text-emerald-600 hover:underline" href="{{ route('password.request') }}">Quên mật khẩu?</a></div>
        @if ($errors->any())<flux:callout variant="danger" heading="Không thể đăng nhập">{{ $errors->first() }}</flux:callout>@endif
        <flux:button type="submit" variant="primary" class="w-full">Đăng nhập</flux:button>
    </form>
</x-layouts.guest>
