<x-layouts.guest title="Đặt lại mật khẩu — SalesFlow CRM">
    <h1 class="text-3xl font-semibold">Đặt lại mật khẩu</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">@csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <flux:input name="email" type="email" label="Email" value="{{ old('email', $request->email) }}" required />
        <flux:input name="password" type="password" label="Mật khẩu mới" required viewable />
        <flux:input name="password_confirmation" type="password" label="Xác nhận mật khẩu" required viewable />
        @if ($errors->any())<flux:callout variant="danger">{{ $errors->first() }}</flux:callout>@endif
        <flux:button type="submit" variant="primary" class="w-full">Cập nhật mật khẩu</flux:button>
    </form>
</x-layouts.guest>
