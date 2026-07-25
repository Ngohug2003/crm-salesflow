<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Quy trình bán hàng (Pipelines)</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý các quy trình bán hàng đa bước và cấu hình các giai đoạn (Stages).</p>
        </div>

        @can('create', App\Models\Pipeline::class)
            <flux:button href="{{ route('pipelines.create') }}" variant="primary" icon="plus" size="sm">
                Tạo Quy trình mới
            </flux:button>
        @endcan
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <div class="crm-card">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="w-full sm:w-72">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Tìm theo tên, mã quy trình..."
                        icon="magnifying-glass"
                    />
                </div>

                <div class="w-full sm:w-48">
                    <flux:select wire:model.live="isActive" placeholder="Trạng thái">
                        <option value="">Tất cả trạng thái</option>
                        <option value="true">Đang hoạt động</option>
                        <option value="false">Tạm ngừng</option>
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="divide-y divide-slate-200 dark:divide-slate-800">
            @forelse ($this->pipelines as $pipe)
                <div class="flex flex-col justify-between gap-4 py-4 sm:flex-row sm:items-center">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('pipelines.show', $pipe->id) }}" class="text-base font-semibold text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">
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
                            <flux:button href="{{ route('pipelines.edit', $pipe->id) }}" size="sm" variant="ghost" icon="pencil">
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
            @empty
                <div class="py-12 text-center text-sm text-slate-500">
                    Không tìm thấy quy trình bán hàng nào phù hợp.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $this->pipelines->links() }}
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
