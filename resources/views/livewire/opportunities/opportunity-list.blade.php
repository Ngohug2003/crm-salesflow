<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Bán hàng</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Cơ hội bán hàng</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Quản lý các cơ hội kinh doanh, theo dõi doanh thu và giá trị dự báo.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 dark:border-slate-800 dark:bg-slate-900">
                <span class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-emerald-600 shadow-sm dark:bg-slate-800 dark:text-emerald-400">
                    <flux:icon.bars-3-bottom-left class="size-4" />
                    Danh sách
                </span>
                <a href="{{ route('opportunities.kanban') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                    <flux:icon.view-columns class="size-4" />
                    Kanban
                </a>
            </div>

            @can('create', App\Models\Opportunity::class)
                <flux:button :href="route('opportunities.create')" wire:navigate variant="primary" icon="plus">
                    Tạo cơ hội
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
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

    <div class="crm-card relative">
        <div class="data-list-heading">
            <div>
                <h2 class="font-semibold">Danh sách cơ hội bán hàng</h2>
                <p class="mt-1 text-sm text-slate-500">Có {{ $this->opportunities->total() }} cơ hội phù hợp trong phạm vi.</p>
            </div>
            @if ($search !== '' || $pipelineId !== '' || $stageId !== '' || $status !== '')
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Xóa bộ lọc</flux:button>
            @endif
        </div>

        <div class="data-list-filters mb-5 xl:grid-cols-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                label="Tìm kiếm"
                placeholder="Tên, mã cơ hội, ghi chú..."
                icon="magnifying-glass"
            />

            <flux:select wire:model.live="pipelineId" label="Quy trình">
                <option value="">Tất cả quy trình</option>
                @foreach ($this->pipelines as $pipelineOption)
                    <option value="{{ $pipelineOption->id }}">{{ $pipelineOption->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" label="Trạng thái">
                <option value="">Tất cả trạng thái</option>
                <option value="open">Đang mở</option>
                <option value="won">Thành công</option>
                <option value="lost">Thất bại</option>
            </flux:select>
        </div>

        <div class="data-list-content">
            <x-data-list.loading target="search,pipelineId,status,clearFilters,gotoPage,nextPage,previousPage" />

            @if ($this->opportunities->isEmpty())
                <x-data-list.empty
                    title="Không tìm thấy cơ hội bán hàng"
                    description="Thử thay đổi từ khóa hoặc các bộ lọc hiện tại."
                    icon="briefcase"
                >
                    @if ($search !== '' || $pipelineId !== '' || $stageId !== '' || $status !== '')
                        <x-slot:action>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Đặt lại bộ lọc</flux:button>
                        </x-slot:action>
                    @endif
                </x-data-list.empty>
            @else
                <div class="data-list-feed">
                    @foreach ($this->opportunities as $opp)
                        <div class="data-list-feed-item">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('opportunities.show', $opp->id) }}" wire:navigate class="text-base font-semibold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
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
                            <flux:button :href="route('opportunities.edit', $opp->id)" wire:navigate size="sm" variant="ghost" icon="pencil">
                                Sửa
                            </flux:button>
                        @endcan

                        @can('delete', $opp)
                            <flux:button wire:click="confirmDeleteOpportunity({{ $opp->id }})" size="sm" variant="danger" icon="trash">
                                Xóa
                            </flux:button>
                        @endcan
                    </div>
                        </div>
                    @endforeach
                </div>

                <x-data-list.pagination :paginator="$this->opportunities" />
            @endif
        </div>

        <!-- Modal Xác nhận xóa Cơ hội bán hàng -->
        <div
            x-data="{ open: @entangle('confirmingDeleteOpportunityId') }"
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
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Xác nhận xóa Cơ hội bán hàng</h3>
                        <p class="text-xs text-slate-500">Hành động này sẽ chuyển cơ hội vào thùng rác.</p>
                    </div>
                </div>

                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Bạn có chắc chắn muốn xóa cơ hội bán hàng này không?
                </p>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <flux:button wire:click="$set('confirmingDeleteOpportunityId', null)" variant="ghost" size="sm">
                        Hủy bỏ
                    </flux:button>
                    <flux:button wire:click="deleteConfirmedOpportunity" variant="danger" size="sm">
                        Xác nhận xóa
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
