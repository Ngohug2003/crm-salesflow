<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Cơ hội bán hàng</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Cơ hội bán hàng</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
                Theo dõi thương vụ, doanh thu dự kiến, giai đoạn pipeline và lịch sử chăm sóc.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 dark:border-slate-800 dark:bg-slate-900">
                <span class="inline-flex min-h-8 items-center gap-1.5 rounded-md bg-white px-3 text-xs font-semibold text-emerald-700 shadow-xs dark:bg-slate-800 dark:text-emerald-300">
                    Danh sách
                </span>
                <a href="{{ route('opportunities.kanban') }}" wire:navigate class="inline-flex min-h-8 items-center gap-1.5 rounded-md px-3 text-xs font-semibold text-slate-600 transition hover:text-slate-950 dark:text-slate-400 dark:hover:text-white">
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
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('message') }}
        </div>
    @endif

    @error('opportunity_error')
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
            {{ $message }}
        </div>
    @enderror

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="crm-card p-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tổng cơ hội</p>
                <p class="mt-1 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($this->summary['total_count']) }}</p>
            </div>
        </div>

        <div class="crm-card p-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Giá trị hợp đồng</p>
                <p class="mt-1 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($this->summary['total_amount']) }} đ</p>
            </div>
        </div>

        <div class="crm-card p-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Doanh thu dự báo</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format($this->summary['total_weighted_value']) }} đ</p>
            </div>
        </div>
    </div>

    <section class="crm-card relative" aria-labelledby="opportunity-list-title">
        <div class="data-list-heading">
            <div>
                <h2 id="opportunity-list-title" class="font-semibold text-slate-950 dark:text-white">Danh sách cơ hội</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Có {{ $this->opportunities->total() }} cơ hội phù hợp trong phạm vi.</p>
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
                        <article class="data-list-feed-item" wire:key="opportunity-row-{{ $opp->id }}">
                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('opportunities.show', $opp->id) }}" wire:navigate class="text-base font-semibold text-slate-950 transition hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                                        {{ $opp->title }}
                                    </a>

                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: {{ $opp->stage?->color }}20; color: {{ $opp->stage?->color }}; border: 1px solid {{ $opp->stage?->color }}40;">
                                        <span class="size-2 rounded-full" style="background-color: {{ $opp->stage?->color }};"></span>
                                        {{ $opp->stage?->name }} · {{ $opp->stage?->probability }}%
                                    </span>

                                    @if ($opp->is_won)
                                        <flux:badge variant="solid" color="emerald" size="sm">Thành công</flux:badge>
                                    @elseif ($opp->is_lost)
                                        <flux:badge variant="solid" color="red" size="sm">Thất bại</flux:badge>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                    <span>Mã: <code class="font-mono text-slate-700 dark:text-slate-300">{{ $opp->code }}</code></span>
                                    @if ($opp->company)
                                        <span>Doanh nghiệp: <strong>{{ $opp->company->name }}</strong></span>
                                    @endif
                                    @if ($opp->contact)
                                        <span>Liên hệ: <strong>{{ $opp->contact->full_name }}</strong></span>
                                    @endif
                                    <span>Phụ trách: {{ $opp->owner?->name ?: 'Hệ thống' }}</span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                    <span class="text-slate-600 dark:text-slate-400">
                                        Giá trị: <strong class="text-slate-950 dark:text-white">{{ number_format((float) $opp->amount) }} đ</strong>
                                    </span>
                                    <span class="text-emerald-700 dark:text-emerald-300">
                                        Dự báo: <strong>{{ number_format($opp->weighted_value) }} đ</strong>
                                    </span>
                                    @if ($opp->expected_close_date)
                                        <span class="text-slate-500 dark:text-slate-400">Dự kiến đóng: {{ $opp->expected_close_date->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <flux:button :href="route('opportunities.show', $opp->id)" wire:navigate size="sm" variant="subtle" icon="eye">
                                    Xem
                                </flux:button>

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
                        </article>
                    @endforeach
                </div>

                <x-data-list.pagination :paginator="$this->opportunities" />
            @endif
        </div>
    </section>

    <div
        x-data="{ open: @entangle('confirmingDeleteOpportunityId') }"
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 grid place-items-center bg-slate-950/60 p-4 backdrop-blur-sm"
    >
        <div
            x-show="open"
            x-transition
            class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-start gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-950 dark:text-white">Xóa cơ hội bán hàng?</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Cơ hội sẽ được chuyển vào thùng rác nếu anh có quyền thực hiện.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
                <flux:button wire:click="$set('confirmingDeleteOpportunityId', null)" variant="ghost" size="sm">Hủy</flux:button>
                <flux:button wire:click="deleteConfirmedOpportunity" variant="danger" size="sm">Xác nhận xóa</flux:button>
            </div>
        </div>
    </div>
</div>
