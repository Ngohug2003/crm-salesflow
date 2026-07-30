<section class="crm-card">
    <div class="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Dòng thời gian hoạt động</h2>
            <p class="mt-1 text-sm text-slate-500">Lịch sử sự kiện, thay đổi hồ sơ, tệp đính kèm và hoạt động tương tác.</p>
        </div>

        <div class="flex items-center gap-2">
            @can('create', App\Models\Activity::class)
                <flux:button wire:click="openCreateActivity" variant="primary" size="sm" icon="plus">
                    Tạo Hoạt động
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session()->has('activity_message'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('activity_message') }}
        </div>
    @endif

    @error('activity_error')
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-xs font-semibold text-red-800 dark:bg-red-950/40 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    <!-- Filter Bar by Activity Type -->
    <div class="mb-5 flex flex-wrap items-center gap-1.5 border-b border-slate-200 dark:border-slate-800 pb-3">
        <button
            type="button"
            wire:click="$set('filterType', '')"
            class="rounded-lg px-2.5 py-1 text-xs font-semibold transition-colors {{ $filterType === '' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800/80 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}"
        >
            Tất cả hoạt động
        </button>
        @foreach (App\Enums\ActivityType::cases() as $type)
            <button
                type="button"
                wire:click="$set('filterType', '{{ $type->value }}')"
                class="rounded-lg px-2.5 py-1 text-xs font-semibold transition-colors {{ $filterType === $type->value ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800/80 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}"
            >
                {{ $type->label() }}
            </button>
        @endforeach
    </div>

    <!-- Timeline Items List -->
    <div class="relative pl-6 border-l-2 border-slate-200 dark:border-slate-800 space-y-6">
        @forelse ($this->timeline as $item)
            <div class="relative group">
                <!-- Bullet Icon -->
                <div class="absolute -left-[31px] top-0 flex size-6 items-center justify-center rounded-full bg-white ring-2 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    @if ($item->type === 'activity')
                        <flux:icon.phone class="size-3 text-indigo-500" />
                    @elseif ($item->type === 'attachment')
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
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-slate-900 dark:text-white text-sm">
                            {{ $item->title }}
                        </span>

                        @if ($item->type === 'activity')
                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                                {{ $item->metadata['activity_type_label'] ?? 'Hoạt động' }}
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <span>{{ $item->timestamp->format('d/m/Y H:i') }} ({{ $item->timestamp->diffForHumans() }})</span>

                        @if ($item->type === 'activity' && isset($item->metadata['activity_id']))
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button type="button" wire:click="editActivity({{ $item->metadata['activity_id'] }})" class="text-slate-400 hover:text-indigo-600 p-0.5">
                                    <flux:icon.pencil class="size-3.5" />
                                </button>
                                <button type="button" wire:click="confirmDeleteActivity({{ $item->metadata['activity_id'] }})" class="text-slate-400 hover:text-red-600 p-0.5" title="Xóa tương tác">
                                    <flux:icon.trash class="size-3.5" />
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <p class="mt-0.5 text-xs text-slate-500">
                    Thực hiện bởi <span class="font-medium text-slate-700 dark:text-slate-300">{{ $item->causer }}</span>
                </p>

                @if ($item->description)
                    <div class="mt-2 rounded-lg bg-slate-50 p-2.5 text-xs font-medium text-slate-700 dark:bg-slate-900/60 dark:text-slate-300 border border-slate-100 dark:border-slate-800 whitespace-pre-line">
                        {{ $item->description }}
                    </div>
                @endif
            </div>
        @empty
            <p class="py-6 text-center text-xs text-slate-400 italic">Chưa có lịch sử hoặc hoạt động nào trong mốc thời gian.</p>
        @endforelse
    </div>

    <!-- Modal Tạo / Sửa Hoạt động tương tác -->
    <div
        x-data="{ open: @entangle('showActivityModal') }"
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
            class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4"
        >
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ $editingActivityId ? 'Cập nhật Tương tác' : 'Tạo Hoạt động tương tác mới' }}
                </h3>
                <button wire:click="$set('showActivityModal', false)" type="button" class="text-slate-400 hover:text-slate-600">
                    <flux:icon.x-mark class="size-5" />
                </button>
            </div>

            <div class="space-y-4">
                <x-forms.smart-select wire:model="activityType" label="Loại tương tác *">
                    @foreach (App\Enums\ActivityType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </x-forms.smart-select>

                <flux:input wire:model="activityTitle" label="Tiêu đề tương tác *" placeholder="VD: Cuộc gọi tư vấn giải pháp, Họp ký hợp đồng..." required />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:input wire:model="activityPerformedAt" type="datetime-local" label="Thời gian thực hiện" />
                    <flux:input wire:model="activityDuration" type="number" label="Thời lượng (Phút)" placeholder="30" />
                </div>

                <flux:input wire:model="activityLocation" label="Địa điểm / Hình thức" placeholder="VD: Zoom, Văn phòng công ty, Điện thoại..." />

                <flux:textarea wire:model="activityDescription" label="Nội dung chi tiết" rows="3" placeholder="Nhập ghi chú hoặc kết quả trao đổi..." />
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <flux:button wire:click="$set('showActivityModal', false)" variant="ghost" size="sm">
                    Hủy
                </flux:button>

                <flux:button wire:click="saveActivity" variant="primary" size="sm">
                    Lưu Hoạt động
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Modal Xác nhận xóa Tương tác -->
    <div
        x-data="{ open: @entangle('confirmingDeleteActivityId') }"
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
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4"
        >
            <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
                <div class="rounded-full bg-red-100 p-2.5 dark:bg-red-950/60">
                    <flux:icon.exclamation-triangle class="size-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Xác nhận xóa tương tác</h3>
                    <p class="text-xs text-slate-500">Hành động này sẽ chuyển tương tác vào thùng rác.</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bạn có chắc chắn muốn xóa hoạt động tương tác này không? Bản ghi sẽ được lưu trữ an toàn dưới dạng xóa mềm.
            </p>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <flux:button wire:click="$set('confirmDeleteActivityId', null)" variant="ghost" size="sm">
                    Hủy bỏ
                </flux:button>
                <flux:button wire:click="deleteConfirmedActivity" variant="danger" size="sm">
                    Xác nhận xóa
                </flux:button>
            </div>
        </div>
    </div>
</section>
