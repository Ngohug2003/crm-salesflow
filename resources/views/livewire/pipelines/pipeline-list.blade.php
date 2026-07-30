<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Quy trình bán hàng</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Quy trình bán hàng</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
                Quản lý pipeline và các giai đoạn bán hàng dùng cho Kanban, cơ hội và báo cáo phễu.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('opportunities.index') }}" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                Cơ hội bán hàng
            </a>

            @can('create', App\Models\Pipeline::class)
                <flux:button :href="route('pipelines.create')" wire:navigate variant="primary" icon="plus">
                    Tạo quy trình
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('message') }}
        </div>
    @endif

    <section class="crm-card relative" aria-labelledby="pipeline-list-title">
        <div class="data-list-heading">
            <div>
                <h2 id="pipeline-list-title" class="font-semibold text-slate-950 dark:text-white">Danh sách quy trình</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Có {{ $this->pipelines->total() }} quy trình phù hợp.</p>
            </div>

            @if ($search !== '' || $isActive !== '')
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Xóa bộ lọc</flux:button>
            @endif
        </div>

        <div class="data-list-filters mb-5 xl:grid-cols-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                label="Tìm kiếm"
                placeholder="Tên hoặc mã quy trình..."
                icon="magnifying-glass"
            />

            <x-forms.smart-select wire:model.live="isActive" label="Trạng thái">
                <option value="">Tất cả trạng thái</option>
                <option value="true">Đang hoạt động</option>
                <option value="false">Tạm ngừng</option>
            </x-forms.smart-select>
        </div>

        <div class="data-list-content">
            <x-data-list.loading target="search,isActive,clearFilters,toggleDefault,toggleActive,gotoPage,nextPage,previousPage" />

            @if ($this->pipelines->isEmpty())
                <x-data-list.empty
                    title="Không tìm thấy quy trình bán hàng"
                    description="Thử thay đổi từ khóa hoặc bộ lọc trạng thái."
                    icon="queue-list"
                >
                    @if ($search !== '' || $isActive !== '')
                        <x-slot:action>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Đặt lại bộ lọc</flux:button>
                        </x-slot:action>
                    @endif
                </x-data-list.empty>
            @else
                <div class="data-list-feed">
                    @foreach ($this->pipelines as $pipe)
                        <article class="data-list-feed-item" wire:key="pipeline-row-{{ $pipe->id }}">
                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('pipelines.show', $pipe->id) }}" wire:navigate class="text-base font-semibold text-slate-950 transition hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                                        {{ $pipe->name }}
                                    </a>

                                    @if ($pipe->is_default)
                                        <flux:badge variant="solid" color="emerald" size="sm">Mặc định</flux:badge>
                                    @endif

                                    @if ($pipe->is_active)
                                        <flux:badge color="emerald" size="sm">Hoạt động</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Tạm ngừng</flux:badge>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                    <span>Mã: <code class="font-mono text-slate-700 dark:text-slate-300">{{ $pipe->code }}</code></span>
                                    <span>{{ $pipe->stages->count() }} giai đoạn</span>
                                    <span>Phụ trách: {{ $pipe->owner?->name ?: 'Hệ thống' }}</span>
                                </div>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    @foreach ($pipe->stages as $stg)
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium text-slate-800 dark:text-slate-200"
                                            style="background-color: {{ $stg->color }}20; border: 1px solid {{ $stg->color }}40;"
                                        >
                                            <span class="size-2 rounded-full" style="background-color: {{ $stg->color }};"></span>
                                            {{ $stg->name }} · {{ $stg->probability }}%
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                @if (! $pipe->is_default)
                                    <flux:button wire:click="toggleDefault({{ $pipe->id }})" size="sm" variant="subtle">
                                        Mặc định
                                    </flux:button>
                                @endif

                                <flux:button wire:click="toggleActive({{ $pipe->id }})" size="sm" variant="ghost">
                                    {{ $pipe->is_active ? 'Tạm ngừng' : 'Kích hoạt' }}
                                </flux:button>

                                @can('update', $pipe)
                                    <flux:button :href="route('pipelines.edit', $pipe->id)" wire:navigate size="sm" variant="ghost" icon="pencil">
                                        Sửa
                                    </flux:button>
                                @endcan

                                @can('delete', $pipe)
                                    <flux:button wire:click="confirmDeletePipeline({{ $pipe->id }})" size="sm" variant="danger" icon="trash">
                                        Xóa
                                    </flux:button>
                                @endcan
                            </div>
                        </article>
                    @endforeach
                </div>

                <x-data-list.pagination :paginator="$this->pipelines" />
            @endif
        </div>
    </section>

    <div
        x-data="{ open: @entangle('confirmingDeletePipelineId') }"
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
                    <h3 class="text-base font-semibold text-slate-950 dark:text-white">Xóa quy trình bán hàng?</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Hành động này chỉ thực hiện được khi quy trình không bị ràng buộc bởi dữ liệu đang sử dụng.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
                <flux:button wire:click="$set('confirmingDeletePipelineId', null)" variant="ghost" size="sm">Hủy</flux:button>
                <flux:button wire:click="deleteConfirmedPipeline" variant="danger" size="sm">Xác nhận xóa</flux:button>
            </div>
        </div>
    </div>
</div>
