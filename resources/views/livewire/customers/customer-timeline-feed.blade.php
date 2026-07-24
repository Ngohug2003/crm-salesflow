<section class="crm-card">
    <div class="mb-5">
        <h2 class="text-lg font-semibold">Dòng thời gian hoạt động</h2>
        <p class="mt-1 text-sm text-slate-500">Lịch sử sự kiện, thay đổi hồ sơ và các tệp đính kèm theo thứ tự thời gian.</p>
    </div>

    <div class="relative pl-6 border-l-2 border-slate-200 dark:border-slate-800 space-y-6">
        @forelse ($this->timeline as $item)
            <div class="relative">
                <!-- Bullet Icon -->
                <div class="absolute -left-[31px] top-0 flex size-6 items-center justify-center rounded-full bg-white ring-2 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    @if ($item->type === 'attachment')
                        <flux:icon.arrow-up-tray class="size-3 text-indigo-500" />
                    @elseif ($item->event === 'created')
                        <flux:icon.plus class="size-3 text-emerald-500" />
                    @elseif ($item->event === 'deleted')
                        <flux:icon.trash class="size-3 text-red-500" />
                    @else
                        <flux:icon.clock class="size-3 text-slate-400" />
                    @endif
                </div>

                <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-center">
                    <span class="font-semibold text-slate-900 dark:text-white text-sm">
                        {{ $item->title }}
                    </span>
                    <span class="text-xs text-slate-400">
                        {{ $item->timestamp->format('d/m/Y H:i') }} ({{ $item->timestamp->diffForHumans() }})
                    </span>
                </div>

                <p class="mt-0.5 text-xs text-slate-500">
                    Thực hiện bởi <span class="font-medium text-slate-700 dark:text-slate-300">{{ $item->causer }}</span>
                </p>

                @if ($item->description)
                    <div class="mt-2 rounded-lg bg-amber-50 p-2.5 text-xs font-medium text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                        {{ $item->description }}
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-500">Chưa có dòng thời gian hoạt động nào được ghi nhận.</p>
        @endforelse
    </div>
</section>
