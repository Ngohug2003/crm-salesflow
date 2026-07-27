@props(['pipelineRequired' => false])

<section class="crm-card space-y-4" aria-label="Bộ lọc báo cáo">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Bộ lọc</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Số liệu luôn được giới hạn theo quyền dữ liệu của tài khoản.</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:button wire:click="openSavePresetModal" variant="subtle" icon="bookmark" size="sm">
                Lưu bộ lọc
            </flux:button>
            <flux:button wire:click="refreshMetrics" wire:loading.attr="disabled" variant="subtle" icon="arrow-path" size="sm">
                Làm mới dữ liệu
            </flux:button>
            <flux:button wire:click="resetFilters" variant="ghost" size="sm">Đặt lại</flux:button>
        </div>
    </div>

    <!-- Saved Presets Bar -->
    @if ($this->savedPresets->count() > 0)
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
            <span class="text-xs font-medium text-slate-500">Bộ lọc đã lưu:</span>
            @foreach ($this->savedPresets as $preset)
                <div class="inline-flex items-center gap-1">
                    <button
                        type="button"
                        wire:click="applyFilterPreset({{ $preset->id }})"
                        @class([
                            'inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium transition',
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-semibold' => $selectedPresetId === $preset->id,
                            'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' => $selectedPresetId !== $preset->id,
                        ])
                    >
                        @if ($preset->is_default)
                            <span class="mr-1 text-amber-500">★</span>
                        @endif
                        {{ $preset->name }}
                    </button>
                    <button
                        type="button"
                        wire:click="deleteFilterPreset({{ $preset->id }})"
                        wire:confirm="Bạn có chắc chắn muốn xóa bộ lọc đã lưu này không?"
                        class="text-slate-400 hover:text-red-600 dark:hover:text-red-400"
                        title="Xóa bộ lọc"
                    >
                        ✕
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <flux:select wire:model.live="datePreset" label="Khoảng thời gian">
            <option value="today">Hôm nay</option>
            <option value="this_week">Tuần này</option>
            <option value="this_month">Tháng này</option>
            <option value="this_quarter">Quý này</option>
            <option value="this_year">Năm nay</option>
            <option value="custom">Khoảng ngày tùy chọn</option>
        </flux:select>

        <flux:select wire:model.live="departmentId" label="Phòng ban">
            <option value="">Tất cả phòng ban được phép xem</option>
            @foreach ($this->departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="userId" label="Người phụ trách">
            <option value="">Tất cả người dùng được phép xem</option>
            @foreach ($this->users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="pipelineId" label="Quy trình bán hàng">
            @unless ($pipelineRequired)
                <option value="">Tất cả quy trình được phép xem</option>
            @endunless
            @foreach ($this->pipelines as $pipeline)
                <option value="{{ $pipeline->id }}">{{ $pipeline->name }}</option>
            @endforeach
        </flux:select>
    </div>

    @if ($datePreset === 'custom')
        <div class="grid grid-cols-1 gap-4 border-t border-slate-200 pt-4 dark:border-slate-800 sm:grid-cols-2">
            <flux:input type="date" wire:model.live="startDate" label="Từ ngày" />
            <flux:input type="date" wire:model.live="endDate" label="Đến ngày" />
        </div>
    @endif

    <div wire:loading.delay class="h-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
        <div class="h-full w-1/3 animate-pulse rounded-full bg-indigo-500"></div>
        <span class="sr-only">Đang cập nhật số liệu báo cáo</span>
    </div>

    <!-- Modal Save Filter Preset -->
    <div
        x-data="{ open: @entangle('showSavePresetModal') }"
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
            <form wire:submit.prevent="saveFilterPreset" class="space-y-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Lưu bộ lọc báo cáo hiện tại</h3>
                    <p class="mt-1 text-xs text-slate-500">Đặt tên để dễ dàng khôi phục bộ lọc chỉ với 1 cú click.</p>
                </div>

                <div class="space-y-3">
                    <flux:input wire:model="presetName" label="Tên bộ lọc *" placeholder="Ví dụ: Doanh thu Tháng này - Miền Nam" required />
                    <flux:checkbox wire:model="presetIsDefault" label="Đặt làm Bộ lọc mặc định khi mở báo cáo" />
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <flux:button wire:click="$set('showSavePresetModal', false)" variant="ghost" size="sm" type="button">Hủy</flux:button>
                    <flux:button type="submit" variant="primary" size="sm" wire:loading.attr="disabled">Lưu bộ lọc</flux:button>
                </div>
            </form>
        </div>
    </div>
</section>
