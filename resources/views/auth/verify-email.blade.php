<x-layouts.guest title="Xác minh email — SalesFlow CRM">
    <h1 class="text-3xl font-semibold">Xác minh email</h1><p class="mt-3 text-slate-500">Vui lòng mở liên kết trong email. Bạn có thể yêu cầu gửi lại nếu chưa nhận được.</p>
    <form method="POST" action="{{ route('verification.send') }}" class="mt-8">@csrf<flux:button type="submit" variant="primary" class="w-full">Gửi lại email</flux:button></form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<flux:button type="submit" variant="ghost" class="w-full">Đăng xuất</flux:button></form>
</x-layouts.guest>
