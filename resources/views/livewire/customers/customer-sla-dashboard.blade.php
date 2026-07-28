<div>
    <!-- Header -->
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Khách hàng / Quản lý SLA</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">SLA Chăm sóc khách hàng</h1>
            <p class="mt-2 max-w-3xl text-slate-500">
                Theo dõi mốc thời gian tương tác với Lead, Cơ hội bán hàng và Doanh nghiệp. Đảm bảo mọi hồ sơ đều được chăm sóc đúng tiến độ.
            </p>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
        <!-- Vi pham SLA Card -->
        <div class="crm-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-red-600 dark:text-red-400">Vi phạm SLA</span>
                <flux:badge color="red" size="sm">Quá hạn</flux:badge>
            </div>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $breachedCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Hồ sơ trễ hạn không có tương tác mới</p>
        </div>

        <!-- Sap qua han SLA Card -->
        <div class="crm-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Sắp quá hạn SLA</span>
                <flux:badge color="amber" size="sm">Cần chú ý</flux:badge>
            </div>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $warningCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Hồ sơ gần chạm mốc trễ hạn</p>
        </div>

        <!-- Dung han SLA Card -->
        <div class="crm-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Đúng hạn SLA</span>
                <flux:badge color="emerald" size="sm">An toàn</flux:badge>
            </div>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $onTrackCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Đã tương tác và chăm sóc gần đây</p>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="mb-6 flex flex-wrap items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <div class="w-full sm:w-64">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Tìm tên hồ sơ..." icon="magnifying-glass" />
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-medium text-slate-500">Loại hồ sơ:</span>
            <x-forms.smart-select wire:model.live="subjectType" class="w-44">
                <option value="all">Tất cả loại</option>
                <option value="lead">Lead (Tiềm năng)</option>
                <option value="opportunity">Cơ hội bán hàng</option>
                <option value="company">Doanh nghiệp</option>
            </x-forms.smart-select>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-medium text-slate-500">Trạng thái SLA:</span>
            <x-forms.smart-select wire:model.live="slaStatus" class="w-44">
                <option value="all">Tất cả trạng thái</option>
                <option value="breached">Vi phạm SLA</option>
                <option value="warning">Sắp quá hạn</option>
                <option value="on_track">Đúng hạn</option>
            </x-forms.smart-select>
        </div>
    </div>

    <!-- SLA Table List -->
    <section class="crm-card">
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Tên hồ sơ / Khách hàng</flux:table.column>
                    <flux:table.column>Phân loại</flux:table.column>
                    <flux:table.column>Người phụ trách</flux:table.column>
                    <flux:table.column>Lần chăm sóc cuối</flux:table.column>
                    <flux:table.column>Mốc hạn SLA</flux:table.column>
                    <flux:table.column align="end">Trạng thái SLA</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($records as $item)
                        <flux:table.row :key="$item['type_code'].'-'.$item['id']">
                            <flux:table.cell variant="strong">
                                <a href="{{ $item['url'] }}" wire:navigate class="text-slate-900 font-semibold hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                                    {{ $item['name'] }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="zinc" size="sm">{{ $item['type_label'] }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $item['owner_name'] }}</flux:table.cell>
                            <flux:table.cell><span class="text-xs text-slate-500">{{ $item['last_interaction_at'] }} ({{ $item['hours_since_interaction'] }} giờ trước)</span></flux:table.cell>
                            <flux:table.cell><span class="text-xs font-mono text-slate-600 dark:text-slate-400">{{ $item['due_at'] }}</span></flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:badge color="{{ $item['status_color'] }}" size="sm">
                                    {{ $item['status_label'] }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-8 text-slate-500">
                                Không tìm thấy hồ sơ chăm sóc khách hàng nào phù hợp với bộ lọc.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </section>
</div>
