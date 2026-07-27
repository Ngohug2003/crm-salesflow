<div
    x-data="{ open: @entangle('showModal') }"
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150 transform"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-4xl max-h-[85vh] flex flex-col rounded-2xl bg-white shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
    >
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ $drillDownData['title'] }}
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Hiển thị tối đa 50 bản ghi gốc phân quyền phù hợp với chỉ số báo cáo.
                </p>
            </div>
            <flux:button wire:click="closeModal" variant="ghost" size="xs" icon="x-mark" />
        </div>

        <!-- Modal Body Table: Single overflow-auto container for both vertical & horizontal scrolling -->
        <div class="flex-1 overflow-auto px-6 pb-6 pt-0">
            @if (empty($drillDownData['rows']))
                <div class="mt-6 rounded-xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Không có bản ghi nào phù hợp</p>
                    <p class="mt-1 text-xs text-slate-500">Chưa có dữ liệu gốc cấu thành chỉ số này theo phạm vi lọc hiện tại.</p>
                </div>
            @else
                <table class="w-full min-w-[52rem] text-left text-sm text-slate-700 dark:text-slate-300">
                    <thead class="sticky top-0 z-10 bg-slate-100 text-xs font-semibold uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300 shadow-xs">
                        <tr>
                            @foreach ($drillDownData['columns'] as $col)
                                <th class="px-4 py-3 whitespace-nowrap">{{ $col }}</th>
                            @endforeach
                            <th class="px-4 py-3 text-right whitespace-nowrap">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($drillDownData['rows'] as $row)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white whitespace-nowrap">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $row['customer'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <flux:badge color="zinc" size="sm">{{ $row['stage'] }}</flux:badge>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $row['owner'] }}</td>
                                <td class="px-4 py-3 font-semibold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">{{ $row['amount'] }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <flux:button :href="$row['url']" wire:navigate size="xs" variant="filled">
                                        Xem chi tiết
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end border-t border-slate-200 px-6 py-3 dark:border-slate-800">
            <flux:button wire:click="closeModal" variant="ghost" size="sm">Đóng cửa sổ</flux:button>
        </div>
    </div>
</div>
