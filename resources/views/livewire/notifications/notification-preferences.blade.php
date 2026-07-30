<div class="space-y-6 pb-12">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <a href="{{ route('notifications.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400" wire:navigate>Thông báo</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Cài đặt nhận thông báo</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Tùy chỉnh Cài đặt Nhận Thông báo</h1>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Tự do bật hoặc tắt kênh nhận thông báo (Hộp thư CRM, Email, Push Broadcast) theo từng loại sự kiện.</p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('notifications.index')" wire:navigate variant="outline" size="sm" icon="inbox">Hộp thư thông báo</flux:button>
            <flux:button wire:click="resetDefaults" variant="ghost" size="sm">Khôi phục mặc định</flux:button>
        </div>
    </div>

    @if ($feedbackMessage)
        <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
            <span>{{ $feedbackMessage }}</span>
            <button type="button" class="text-xs font-semibold hover:underline" wire:click="$set('feedbackMessage', '')">Ẩn</button>
        </div>
    @endif

    <form wire:submit.prevent="savePreferences" class="space-y-6">
        <div class="crm-card p-4 sm:p-6 overflow-x-auto">
            <flux:table class="w-full">
                <flux:table.columns>
                    <flux:table.column class="py-3 px-4">Loại sự kiện thông báo</flux:table.column>
                    <flux:table.column align="center" class="w-36 py-3 px-4">Hộp thư CRM</flux:table.column>
                    <flux:table.column align="center" class="w-36 py-3 px-4">Email thông báo</flux:table.column>
                    <flux:table.column align="center" class="w-36 py-3 px-4">Thông báo nổi (Web)</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach (\App\Enums\NotificationEventCategory::cases() as $category)
                        <flux:table.row :key="$category->value">
                            <flux:table.cell class="py-3.5 px-4">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $category->label() }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $category->description() }}</p>
                            </flux:table.cell>

                            <flux:table.cell align="center" class="py-3.5 px-4">
                                <flux:checkbox wire:model="preferences.{{ $category->value }}.database" />
                            </flux:table.cell>

                            <flux:table.cell align="center" class="py-3.5 px-4">
                                <flux:checkbox wire:model="preferences.{{ $category->value }}.email" />
                            </flux:table.cell>

                            <flux:table.cell align="center" class="py-3.5 px-4">
                                <flux:checkbox wire:model="preferences.{{ $category->value }}.broadcast" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <flux:button type="button" wire:click="resetDefaults" variant="ghost">Hủy thay đổi</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Lưu cài đặt nhận thông báo</flux:button>
        </div>
    </form>
</div>
