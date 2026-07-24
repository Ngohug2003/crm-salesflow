<div class="space-y-6" x-data="{ dragOppId: null, dragFromStageId: null, isDragging: false }">
    <!-- Top Header -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Quy trình Bán hàng dạng Kanban</h1>
            <p class="mt-1 text-sm text-slate-500">Kéo thả các thẻ Cơ hội bán hàng để chuyển Giai đoạn trực quan.</p>
        </div>

        <div class="flex items-center gap-3">
            <!-- View Mode Switcher -->
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 dark:border-slate-800 dark:bg-slate-900">
                <a href="{{ route('opportunities.index') }}" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                    <flux:icon.bars-3-bottom-left class="size-4" />
                    Danh sách
                </a>
                <span class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-indigo-600 shadow-sm dark:bg-slate-800 dark:text-indigo-400">
                    <flux:icon.view-columns class="size-4" />
                    Kanban
                </span>
            </div>

            @can('create', App\Models\Opportunity::class)
                <flux:button href="{{ route('opportunities.create') }}" variant="primary" icon="plus" size="sm">
                    Tạo Cơ hội mới
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    @error('kanban_error')
        <div class="rounded-lg bg-red-50 p-4 text-sm font-semibold text-red-800 dark:bg-red-950/40 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    <!-- Filter Bar Grid -->
    <div class="crm-card">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <flux:select wire:model.live="pipelineId" label="Quy trình Bán hàng">
                    @foreach ($this->pipelines as $pipe)
                        <option value="{{ $pipe->id }}">{{ $pipe->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    label="Tìm kiếm"
                    placeholder="Tìm tên, mã cơ hội..."
                    icon="magnifying-glass"
                />
            </div>

            <div>
                <flux:select wire:model.live="ownerId" label="Người phụ trách">
                    <option value="">Tất cả người phụ trách</option>
                    @foreach ($this->users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select wire:model.live="status" label="Trạng thái">
                    <option value="">Tất cả (All)</option>
                    <option value="open">Đang mở (Open)</option>
                    <option value="won">Thành công (Won)</option>
                    <option value="lost">Thất bại (Lost)</option>
                </flux:select>
            </div>
        </div>
    </div>

    <!-- Kanban Board Container -->
    <div class="flex gap-4 overflow-x-auto pb-6 pt-1 items-start min-h-[500px]">
        @forelse ($this->stageColumns as $col)
            @php
                $stage = $col['stage'];
                $opps = $col['opportunities'];
            @endphp
            <div
                class="flex w-80 shrink-0 flex-col rounded-xl border border-slate-200 bg-slate-100/80 dark:border-slate-800 dark:bg-slate-900/80 p-3 transition-colors shadow-sm"
                @dragover.prevent="$el.classList.add('ring-2', 'ring-indigo-500', 'bg-indigo-50/50', 'dark:bg-indigo-950/40')"
                @dragleave="$el.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50/50', 'dark:bg-indigo-950/40')"
                @drop.prevent="
                    $el.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50/50', 'dark:bg-indigo-950/40');
                    if (dragOppId && dragFromStageId !== {{ $stage->id }}) {
                        $wire.moveOpportunity(dragOppId, {{ $stage->id }}, dragFromStageId);
                    }
                "
            >
                <!-- Stage Header -->
                <div class="mb-3 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="size-3 shrink-0 rounded-full" style="background-color: {{ $stage->color }};"></span>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate" title="{{ $stage->name }}">
                                {{ $stage->name }}
                            </h3>
                            <span class="shrink-0 rounded-full bg-slate-200 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                {{ $col['total_count'] }}
                            </span>
                        </div>
                        <span class="text-xs font-semibold text-slate-500 shrink-0">{{ $stage->probability }}%</span>
                    </div>

                    <div class="mt-2 flex items-center justify-between text-[11px] font-medium text-slate-500">
                        <span>Tổng: <strong class="text-slate-800 dark:text-slate-200">{{ number_format($col['total_amount']) }} đ</strong></span>
                        <span class="text-indigo-600 dark:text-indigo-400">Weighted: <strong>{{ number_format($col['total_weighted_value']) }} đ</strong></span>
                    </div>
                </div>

                <!-- Cards List -->
                <div class="flex flex-col gap-3 min-h-[200px]">
                    @forelse ($opps as $opp)
                        <div
                            draggable="true"
                            @dragstart="dragOppId = {{ $opp->id }}; dragFromStageId = {{ $stage->id }}; isDragging = true; $el.classList.add('opacity-40')"
                            @dragend="isDragging = false; $el.classList.remove('opacity-40')"
                            class="crm-card cursor-grab active:cursor-grabbing p-3.5 space-y-2.5 hover:shadow-md transition-shadow group relative border border-slate-200 dark:border-slate-800"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('opportunities.show', $opp->id) }}" class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400 leading-snug">
                                    {{ $opp->title }}
                                </a>
                            </div>

                            <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                <span class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-slate-700 dark:text-slate-300">{{ $opp->code }}</span>
                                @if ($opp->is_won)
                                    <flux:badge variant="solid" color="emerald" size="sm">Won</flux:badge>
                                @elseif ($opp->is_lost)
                                    <flux:badge variant="solid" color="red" size="sm">Lost</flux:badge>
                                @endif
                            </div>

                            @if ($opp->company)
                                <div class="text-xs font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <flux:icon.building-office-2 class="size-3.5 text-slate-400 shrink-0" />
                                    <span class="truncate">{{ $opp->company->name }}</span>
                                </div>
                            @endif

                            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white">{{ number_format((float) $opp->amount) }} đ</p>
                                    <p class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400">Weighted: {{ number_format($opp->weighted_value) }} đ</p>
                                </div>

                                <div class="flex items-center gap-1">
                                    <div class="size-6 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 grid place-items-center font-bold text-[10px]" title="{{ $opp->owner?->name ?: 'Hệ thống' }}">
                                        {{ strtoupper(substr($opp->owner?->name ?: 'U', 0, 1)) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-xs text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-lg">
                            Kéo thẻ vào đây
                        </div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="py-12 text-center text-sm text-slate-500 w-full">
                Không tìm thấy Quy trình bán hàng nào.
            </div>
        @endforelse
    </div>
</div>
