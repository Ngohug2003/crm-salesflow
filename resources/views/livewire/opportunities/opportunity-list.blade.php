<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Cơ hội bán hàng (Opportunities)</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý các cơ hội kinh doanh, theo dõi doanh thu và giá trị dự báo (Weighted Value).</p>
        </div>

        @can('create', App\Models\Opportunity::class)
            <flux:button href="{{ route('opportunities.create') }}" variant="primary" icon="plus" size="sm">
                Tạo Cơ hội mới
            </flux:button>
        @endcan
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <!-- Thẻ Thống kê Doanh thu & Weighted Value -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="crm-card flex items-center justify-between p-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tổng số cơ hội</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($this->summary['total_count']) }}</p>
            </div>
            <div class="grid size-10 place-items-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                <flux:icon.briefcase class="size-6" />
            </div>
        </div>

        <div class="crm-card flex items-center justify-between p-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tổng Giá trị Hợp đồng</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($this->summary['total_amount']) }} đ</p>
            </div>
            <div class="grid size-10 place-items-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                <flux:icon.banknotes class="size-6" />
            </div>
        </div>

        <div class="crm-card flex items-center justify-between p-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tổng Doanh thu Dự báo (Weighted)</p>
                <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($this->summary['total_weighted_value']) }} đ</p>
            </div>
            <div class="grid size-10 place-items-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                <flux:icon.chart-bar class="size-6" />
            </div>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="crm-card">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="w-full sm:w-72">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Tìm tên, mã cơ hội, ghi chú..."
                        icon="magnifying-glass"
                    />
                </div>

                <div class="w-full sm:w-48">
                    <flux:select wire:model.live="pipelineId" placeholder="Quy trình">
                        <option value="">Tất cả Quy trình</option>
                        @foreach ($this->pipelines as $pipe)
                            <option value="{{ $pipe->id }}">{{ $pipe->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="w-full sm:w-44">
                    <flux:select wire:model.live="status" placeholder="Trạng thái">
                        <option value="">Tất cả trạng thái</option>
                        <option value="open">Đang mở (Open)</option>
                        <option value="won">Thành công (Won)</option>
                        <option value="lost">Thất bại (Lost)</option>
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="divide-y divide-slate-200 dark:divide-slate-800">
            @forelse ($this->opportunities as $opp)
                <div class="flex flex-col justify-between gap-4 py-4 sm:flex-row sm:items-center">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('opportunities.show', $opp->id) }}" class="text-base font-semibold text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">
                                {{ $opp->title }}
                            </a>

                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background-color: {{ $opp->stage?->color }}20; color: {{ $opp->stage?->color }}; border: 1px solid {{ $opp->stage?->color }}40;">
                                <span class="size-2 rounded-full" style="background-color: {{ $opp->stage?->color }};"></span>
                                {{ $opp->stage?->name }} ({{ $opp->stage?->probability }}%)
                            </span>

                            @if ($opp->is_won)
                                <flux:badge variant="solid" color="emerald" size="sm">Won</flux:badge>
                            @elseif ($opp->is_lost)
                                <flux:badge variant="solid" color="red" size="sm">Lost</flux:badge>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span>Mã: <code class="font-mono text-slate-700 dark:text-slate-300">{{ $opp->code }}</code></span>
                            <span>•</span>
                            @if ($opp->company)
                                <span>Doanh nghiệp: <strong>{{ $opp->company->name }}</strong></span>
                                <span>•</span>
                            @endif
                            @if ($opp->contact)
                                <span>Người liên hệ: <strong>{{ $opp->contact->full_name }}</strong></span>
                                <span>•</span>
                            @endif
                            <span>Người phụ trách: {{ $opp->owner?->name ?: 'Hệ thống' }}</span>
                        </div>

                        <div class="flex items-center gap-4 text-xs font-medium pt-1">
                            <span class="text-slate-700 dark:text-slate-300">
                                Giá trị: <strong class="text-slate-900 dark:text-white text-sm">{{ number_format((float) $opp->amount) }} đ</strong>
                            </span>
                            <span class="text-indigo-600 dark:text-indigo-400">
                                Dự báo (Weighted): <strong>{{ number_format($opp->weighted_value) }} đ</strong>
                            </span>
                            @if ($opp->expected_close_date)
                                <span class="text-slate-500">
                                    Dự kiến đóng: {{ $opp->expected_close_date->format('d/m/Y') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @can('update', $opp)
                            <flux:button href="{{ route('opportunities.edit', $opp->id) }}" size="sm" variant="ghost" icon="pencil">
                                Sửa
                            </flux:button>
                        @endcan

                        @can('delete', $opp)
                            <flux:button wire:click="deleteOpportunity({{ $opp->id }})" wire:confirm="Bạn có chắc chắn muốn xóa cơ hội bán hàng này không?" size="sm" variant="danger" icon="trash">
                                Xóa
                            </flux:button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-sm text-slate-500">
                    Không tìm thấy cơ hội bán hàng nào phù hợp.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $this->opportunities->links() }}
        </div>
    </div>
</div>
