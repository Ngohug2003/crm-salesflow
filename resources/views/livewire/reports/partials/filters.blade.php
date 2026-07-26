@props(['pipelineRequired' => false])

<section class="crm-card space-y-4" aria-label="Bộ lọc báo cáo">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Bộ lọc</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Số liệu luôn được giới hạn theo quyền dữ liệu của tài khoản.</p>
        </div>
        <div class="flex gap-2">
            <flux:button wire:click="refreshMetrics" wire:loading.attr="disabled" variant="subtle" icon="arrow-path" size="sm">
                Làm mới dữ liệu
            </flux:button>
            <flux:button wire:click="resetFilters" variant="ghost" size="sm">Đặt lại</flux:button>
        </div>
    </div>

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
</section>
