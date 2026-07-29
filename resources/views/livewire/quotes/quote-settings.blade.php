<div>
    <div class="mb-8">
        <p class="text-sm text-slate-500">Quản trị / Báo giá</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">Cấu hình báo giá</h1>
        <p class="mt-2 max-w-2xl text-slate-500">Thông tin doanh nghiệp được đóng dấu vào snapshot khi phát hành PDF.</p>
    </div>

    @if (session()->has('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>
    @endif
    @if ($errorMessage)
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $errorMessage }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <section class="crm-card p-5">
            <div class="mb-5">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Nhận diện doanh nghiệp</h2>
                <p class="mt-1 text-sm text-slate-500">Hiển thị ở đầu và phần điều khoản của PDF.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="companyName" label="Tên doanh nghiệp" required />
                <flux:input wire:model="taxCode" label="Mã số thuế" />
                <div class="md:col-span-2">
                    <flux:input wire:model="logo" type="file" accept=".png,.jpg,.jpeg" label="Logo doanh nghiệp" />
                    <p class="mt-1 text-xs text-slate-500">PNG hoặc JPG, tối đa 2 MB. {{ $currentLogoPath ? 'Đang dùng: '.basename($currentLogoPath) : 'Chưa có logo.' }}</p>
                </div>
                <flux:input wire:model="hotline" label="Hotline" />
                <flux:input wire:model="email" type="email" label="Email" />
                <div class="md:col-span-2"><flux:input wire:model="address" label="Địa chỉ" /></div>
                <flux:textarea wire:model="paymentTerms" label="Điều khoản thanh toán mặc định" rows="4" />
                <flux:textarea wire:model="bankInformation" label="Thông tin chuyển khoản" rows="4" />
            </div>
        </section>

        <section class="crm-card overflow-hidden">
            <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Ma trận phê duyệt chiết khấu</h2>
                <p class="mt-1 text-sm text-slate-500">Các khoảng không được giao nhau. Để trống ngưỡng tối đa cho khoảng cuối.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-900/60">
                        <tr><th class="px-5 py-3">Quy tắc</th><th class="px-5 py-3">Từ (%)</th><th class="px-5 py-3">Đến (%)</th><th class="px-5 py-3">Cấp duyệt</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($rules as $index => $rule)
                            <tr wire:key="setting-rule-{{ $rule['id'] }}">
                                <td class="px-5 py-4 font-medium text-slate-900 dark:text-white">{{ $rule['name'] }}</td>
                                <td class="px-5 py-4"><flux:input wire:model="rules.{{ $index }}.minimum_discount_percent" type="number" step="0.01" min="0" max="100" /></td>
                                <td class="px-5 py-4"><flux:input wire:model="rules.{{ $index }}.maximum_discount_percent" type="number" step="0.01" min="0" max="100" placeholder="Không giới hạn" /></td>
                                <td class="px-5 py-4">{{ $rule['auto_approve'] ? 'Tự động duyệt' : ($rule['required_role'] ?? '—') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Lưu cấu hình</span>
                <span wire:loading wire:target="save">Đang lưu…</span>
            </flux:button>
        </div>
    </form>
</div>
