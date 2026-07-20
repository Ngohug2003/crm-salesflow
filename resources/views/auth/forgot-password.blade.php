<x-layouts.guest title="Quên mật khẩu — SalesFlow CRM">
    <h1 class="text-3xl font-semibold">Khôi phục mật khẩu</h1><p class="mt-2 text-sm text-slate-500">Nếu email hợp lệ, chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu.</p>
    @if (session('status'))<div class="mt-5"><flux:callout variant="success">{{ session('status') }}</flux:callout></div>@endif
    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">@csrf
        <flux:input name="email" type="email" label="Email" value="{{ old('email') }}" required autofocus />
        @error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        <flux:button type="submit" variant="primary" class="w-full">Gửi liên kết khôi phục</flux:button>
        <a href="{{ route('login') }}" class="block text-center text-sm text-slate-500 hover:text-slate-900">Quay lại đăng nhập</a>
    </form>
</x-layouts.guest>
