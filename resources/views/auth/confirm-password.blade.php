<x-layouts.guest title="Xác nhận mật khẩu — SalesFlow CRM">
    <h1 class="text-3xl font-semibold">Xác nhận mật khẩu</h1>
    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">@csrf
        <flux:input name="password" type="password" label="Mật khẩu" required viewable />
        <flux:button type="submit" variant="primary" class="w-full">Xác nhận</flux:button>
    </form>
</x-layouts.guest>
