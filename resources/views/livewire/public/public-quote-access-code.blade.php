<section class="mx-auto max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <flux:heading size="lg">Nhập mã truy cập</flux:heading>
    <flux:text class="mt-2">Báo giá này được bảo vệ bằng mã truy cập do đơn vị cung cấp gửi riêng cho bạn.</flux:text>

    <form wire:submit="submitAccessCode" class="mt-6 space-y-4">
        <flux:field>
            <flux:label>Mã truy cập</flux:label>
            <flux:input wire:model="accessCode" type="password" autocomplete="one-time-code" autofocus />
            <flux:error name="accessCode" />
        </flux:field>

        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="submitAccessCode">
            Mở báo giá
        </flux:button>
    </form>
</section>
