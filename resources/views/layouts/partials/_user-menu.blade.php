{{-- User dropdown menu (topbar) --}}
<flux:dropdown position="bottom" align="end">
    <flux:button variant="ghost" class="flex items-center gap-2 !px-2">
        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
        <span class="hidden max-w-32 truncate text-sm font-medium sm:inline">{{ auth()->user()->name }}</span>
        <flux:icon.chevron-down class="size-4 text-slate-400" />
    </flux:button>

    <flux:menu>
        <flux:menu.heading>
            <div class="px-1">
                <p class="font-medium">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
            </div>
        </flux:menu.heading>

        <flux:menu.separator />

        <flux:menu.item :href="route('sessions.index')" wire:navigate icon="device-phone-mobile">
            Phiên đăng nhập
        </flux:menu.item>

        <flux:menu.item :href="route('help.roles')" wire:navigate icon="question-mark-circle">
            Vai trò & quyền
        </flux:menu.item>

        <flux:menu.separator />

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle" variant="danger">
                Đăng xuất
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
