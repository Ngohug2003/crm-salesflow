<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Bán hàng</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Quy trình bán hàng</h1>
            <p class="mt-2 max-w-3xl text-slate-500">Quản lý các quy trình bán hàng đa bước và cấu hình các giai đoạn.</p>
        </div>

        @can('create', App\Models\Pipeline::class)
            <flux:button :href="route('pipelines.create')" wire:navigate variant="primary" icon="plus">
                Tạo quy trình
            </flux:button>
        @endcan
    </div>

    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('message') }}
        </div>
    @endif

    <div class="crm-card relative">
        <div class="data-list-heading">
            <div>
                <h2 class="font-semibold">Danh sách quy trình bán hàng</h2>
                <p class="mt-1 text-sm text-slate-500">Có {{ $this->pipelines->total() }} quy trình phù hợp.</p>
            </div>
            @if ($search !== '' || $isActive !== '')
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Xóa bộ lọc</flux:button>
            @endif
        </div>

        <div class="mb-5 grid gap-3 md:grid-cols-2 xl:max-w-2xl">
            <flux:input
                wire:model.live.debounce.300ms="search"
                label="Tìm kiếm"
                placeholder="Tên hoặc mã quy trình..."
                icon="magnifying-glass"
            />

            <flux:select wire:model.live="isActive" label="Trạng thái">
                <option value="">Tất cả trạng thái</option>
                <option value="true">Đang hoạt động</option>
                <option value="false">Tạm ngừng</option>
            </flux:select>
        </div>

        <div class="data-list-content">
            <x-data-list.loading target="search,isActive,clearFilters,gotoPage,nextPage,previousPage" />

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
                        <div class="data-list-feed-item">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('pipelines.show', $pipe->id) }}" wire:navigate class="text-base font-semibold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                                {{ $pipe->name }}
                            </a>

                            @if ($pipe->is_default)
                                <flux:badge variant="solid" color="emerald" size="sm">Mặc định</flux:badge>
                            @endif

                            @if ($pipe->is_active)
                                <flux:badge color="blue" size="sm">Hoạt động</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Tạm ngừng</flux:badge>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span>Mã: <code class="font-mono text-slate-700 dark:text-slate-300">{{ $pipe->code }}</code></span>
                            <span>•</span>
                            <span>Số giai đoạn: <strong>{{ $pipe->stages->count() }}</strong></span>
                            <span>•</span>
                            <span>Người phụ trách: {{ $pipe->owner?->name ?: 'Hệ thống' }}</span>
                        </div>

                        <!-- Stage preview badges -->
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            @foreach ($pipe->stages as $stg)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium text-slate-800 dark:text-slate-200"
                                    style="background-color: {{ $stg->color }}20; border: 1px solid {{ $stg->color }}40;"
                                >
                                    <span class="size-2 rounded-full" style="background-color: {{ $stg->color }};"></span>
                                    {{ $stg->name }} ({{ $stg->probability }}%)
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if (! $pipe->is_default)
                            <flux:button wire:click="toggleDefault({{ $pipe->id }})" size="sm" variant="subtle">
                                Đặt mặc định
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
                        </div>
                    @endforeach
                </div>

                <x-data-list.pagination :paginator="$this->pipelines" />
            @endif
        </div>
    </div>

    <!-- Modal Xác nhận xóa Quy trình bán hàng -->
    <div
        x-data="{ open: @entangle('confirmingDeletePipelineId') }"
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
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Xác nhận xóa Quy trình bán hàng</h3>
                    <p class="text-xs text-slate-500">Hành động này sẽ xóa quy trình khỏi hệ thống.</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bạn có chắc chắn muốn xóa quy trình bán hàng này không?
            </p>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <flux:button wire:click="$set('confirmingDeletePipelineId', null)" variant="ghost" size="sm">
                    Hủy bỏ
                </flux:button>
                <flux:button wire:click="deleteConfirmedPipeline" variant="danger" size="sm">
                    Xác nhận xóa
                </flux:button>
            </div>
        </div>
    </div>
</div>
