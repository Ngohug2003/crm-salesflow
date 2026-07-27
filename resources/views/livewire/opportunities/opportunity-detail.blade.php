<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <a href="{{ route('opportunities.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Cơ hội bán hàng</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Chi tiết</span>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ $opportunity->title }}</h1>

                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold" style="background-color: {{ $opportunity->stage?->color }}20; color: {{ $opportunity->stage?->color }}; border: 1px solid {{ $opportunity->stage?->color }}40;">
                    <span class="size-2 rounded-full" style="background-color: {{ $opportunity->stage?->color }};"></span>
                    {{ $opportunity->stage?->name }} · {{ $opportunity->stage?->probability }}%
                </span>

                @if ($opportunity->is_won)
                    <flux:badge variant="solid" color="emerald" size="sm">Thành công</flux:badge>
                @elseif ($opportunity->is_lost)
                    <flux:badge variant="solid" color="red" size="sm">Thất bại</flux:badge>
                @endif
            </div>

            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Mã cơ hội: <code class="font-mono text-slate-700 dark:text-slate-300">{{ $opportunity->code }}</code></p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button href="{{ route('opportunities.index') }}" wire:navigate variant="ghost" size="sm">
                Danh sách
            </flux:button>

            @if (! $opportunity->is_won && ! $opportunity->is_lost)
                <flux:button wire:click="$set('showWonModal', true)" variant="primary" color="emerald" size="sm">
                    Chốt thắng
                </flux:button>

                <flux:button wire:click="$set('showLostModal', true)" variant="danger" size="sm">
                    Báo thua
                </flux:button>
            @else
                <flux:button wire:click="$set('showReopenModal', true)" variant="filled" size="sm">
                    Mở lại
                </flux:button>
            @endif

            @can('update', $opportunity)
                <flux:button href="{{ route('opportunities.edit', $opportunity->id) }}" wire:navigate variant="subtle" size="sm">
                    Sửa
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('message') }}
        </div>
    @endif

    @error('stage_error')
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
            {{ $message }}
        </div>
    @enderror

    <section class="crm-card space-y-4" aria-labelledby="opportunity-stage-title">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 id="opportunity-stage-title" class="text-base font-semibold text-slate-950 dark:text-white">Tiến trình: {{ $opportunity->pipeline?->name }}</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">Bấm vào giai đoạn để chuyển nhanh</span>
        </div>

        <div class="flex gap-2 overflow-x-auto pb-2">
            @foreach ($opportunity->pipeline?->stages ?? [] as $stg)
                @php
                    $isCurrent = $stg->id === $opportunity->stage_id;
                    $isPassed = $stg->position < ($opportunity->stage?->position ?? 0);
                @endphp
                <button
                    type="button"
                    wire:click="changeStage({{ $stg->id }})"
                    class="flex min-w-[150px] flex-1 flex-col gap-2 rounded-lg border p-3 text-left transition {{ $isCurrent ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/20' : ($isPassed ? 'border-emerald-200 bg-emerald-50/30 dark:border-emerald-900/50 dark:bg-emerald-950/10' : 'border-slate-200 bg-slate-50 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900/50') }}"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold {{ $isCurrent ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-400' }}">
                            {{ $stg->position }}. {{ $stg->name }}
                        </span>
                        <span class="text-[10px] font-semibold text-slate-500">{{ $stg->probability }}%</span>
                    </div>

                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                        <div class="h-full transition-all" style="width: {{ $stg->probability }}%; background-color: {{ $stg->color }};"></div>
                    </div>
                </button>
            @endforeach
        </div>
    </section>

    <!-- Main 2-Column Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left 2-Columns: Tasks, Files & Timeline -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Danh sách Công việc (Tasks) thuộc Cơ hội bán hàng này -->
            <section class="crm-card space-y-4" aria-labelledby="opportunity-tasks-title">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <h3 id="opportunity-tasks-title" class="text-base font-semibold text-slate-950 dark:text-white">Công việc liên quan</h3>
                        @if ($opportunity->tasks->count() > 0)
                            <flux:badge color="emerald" size="sm">{{ $opportunity->tasks->count() }}</flux:badge>
                        @endif
                    </div>

                    @can('create', App\Models\Task::class)
                        <flux:button
                            href="{{ route('tasks.create', ['subject_type' => App\Models\Opportunity::class, 'subject_id' => $opportunity->id]) }}"
                            wire:navigate
                            variant="primary"
                            size="sm"
                        >
                            Tạo công việc
                        </flux:button>
                    @endcan
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($opportunity->tasks as $t)
                        <div class="flex flex-col justify-between gap-3 py-3 sm:flex-row sm:items-center">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a
                                        href="{{ route('tasks.show', $t->id) }}"
                                        wire:navigate
                                        class="text-sm font-semibold text-slate-950 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400"
                                    >
                                        {{ $t->title }}
                                    </a>

                                    <flux:badge color="{{ $t->status->color() }}" size="sm">
                                        {{ $t->status->label() }}
                                    </flux:badge>

                                    <flux:badge color="{{ $t->priority->color() }}" size="sm" variant="subtle">
                                        {{ $t->priority->label() }}
                                    </flux:badge>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 pt-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span>Phân công:</span>
                                        @php
                                            $mems = collect();
                                            if ($t->assignee) $mems->push($t->assignee);
                                            foreach ($t->assignees as $a) $mems->push($a);
                                            $uniq = $mems->unique('id');
                                        @endphp
                                        @if ($uniq->count() > 0)
                                            <div class="flex items-center -space-x-1.5">
                                                @foreach ($uniq->take(4) as $mb)
                                                    <span title="{{ $mb->name }}" class="inline-flex size-5 items-center justify-center rounded-full border border-white bg-slate-700 text-[8px] font-semibold text-white shadow-sm dark:border-slate-900 dark:bg-slate-600">
                                                        {{ mb_strtoupper(mb_substr($mb->name, 0, 1)) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                            <span class="text-slate-400 font-medium">{{ $uniq->pluck('name')->implode(', ') }}</span>
                                        @else
                                            <span class="text-slate-400 italic">Chưa phân công</span>
                                        @endif
                                    </div>

                                    @if ($t->due_date)
                                        <span>•</span>
                                        <span class="{{ $t->due_date->isPast() && !$t->status->isFinished() ? 'font-bold text-red-600 dark:text-red-400' : '' }}">
                                            Hạn chót: {{ $t->due_date->format('d/m/Y H:i') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <flux:button href="{{ route('tasks.show', $t->id) }}" wire:navigate size="sm" variant="subtle">
                                    Xem
                                </flux:button>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-slate-400">
                            Chưa có công việc nào gắn với Cơ hội bán hàng này.
                        </div>
                    @endforelse
                </div>
            </section>

            <!-- Tệp đính kèm -->
            <livewire:customers.customer-attachment-manager :modelType="App\Models\Opportunity::class" :modelId="$opportunity->id" />

            <!-- Nhật ký & Timeline -->
            <livewire:customers.customer-timeline-feed :modelType="App\Models\Opportunity::class" :modelId="$opportunity->id" />
        </div>

        <!-- Right 1-Column: Thống kê & Thông tin thương mại -->
        <div class="space-y-6">
            <section class="crm-card space-y-4" aria-labelledby="opportunity-commerce-title">
                <h3 id="opportunity-commerce-title" class="text-base font-semibold text-slate-950 dark:text-white">Thông tin thương mại</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500">Giá trị Hợp đồng:</span>
                        <strong class="text-base text-slate-900 dark:text-white">{{ number_format((float) $opportunity->amount) }} đ</strong>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500">Xác suất thành công:</span>
                        <strong class="text-slate-900 dark:text-white">{{ $opportunity->stage?->probability }}%</strong>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500">Giá trị Dự báo (Weighted):</span>
                        <strong class="text-base text-emerald-700 dark:text-emerald-300">{{ number_format($opportunity->weighted_value) }} đ</strong>
                    </div>

                    @if ($opportunity->expected_close_date)
                        <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-500">Dự kiến đóng:</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200">{{ $opportunity->expected_close_date->format('d/m/Y') }}</span>
                        </div>
                    @endif

                    @if ($opportunity->actual_close_date)
                        <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-500">Ngày đóng thực tế:</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200">{{ $opportunity->actual_close_date->format('d/m/Y') }}</span>
                        </div>
                    @endif

                    @if ($opportunity->lost_reason)
                        <div class="py-1 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-500 block">Lý do thất bại:</span>
                            <p class="font-medium text-red-600 dark:text-red-400 text-xs mt-1">{{ $opportunity->lost_reason }}</p>
                        </div>
                    @endif

                    <div>
                        <span class="text-slate-500">Doanh nghiệp:</span>
                        @if ($opportunity->company)
                            <a href="{{ route('companies.show', $opportunity->company->id) }}" class="block font-medium text-emerald-700 hover:underline dark:text-emerald-300">
                                {{ $opportunity->company->name }}
                            </a>
                        @else
                            <p class="font-medium text-slate-400 italic">Chưa gắn Doanh nghiệp</p>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-500">Người liên hệ:</span>
                        @if ($opportunity->contact)
                            <a href="{{ route('contacts.show', $opportunity->contact->id) }}" class="block font-medium text-emerald-700 hover:underline dark:text-emerald-300">
                                {{ $opportunity->contact->full_name }}
                            </a>
                        @else
                            <p class="font-medium text-slate-400 italic">Chưa gắn Người liên hệ</p>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-500">Người phụ trách:</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200">{{ $opportunity->owner?->name ?: 'Hệ thống' }}</p>
                    </div>

                    <div>
                        <span class="text-slate-500">Phòng ban:</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200">{{ $opportunity->department?->name ?: 'Hệ thống' }}</p>
                    </div>
                </div>
            </section>

            @if ($opportunity->notes)
                <section class="crm-card space-y-2" aria-labelledby="opportunity-notes-title">
                    <h3 id="opportunity-notes-title" class="text-base font-semibold text-slate-950 dark:text-white">Ghi chú</h3>
                    <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ $opportunity->notes }}</p>
                </section>
            @endif
        </div>
    </div>

    <!-- Modal Nhập Lý do Thất bại (Close Lost) -->
    <div
        x-data="{ open: @entangle('showLostModal') }"
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
            class="w-full max-w-md space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-950 dark:text-white">Báo thua cơ hội bán hàng</h3>
                <button wire:click="$set('showLostModal', false)" type="button" class="text-slate-400 hover:text-slate-600">
                    <flux:icon.x-mark class="size-5" />
                </button>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-400">Vui lòng cung cấp lý do thất bại để hoàn tất đóng cơ hội này.</p>

            <div>
                <flux:textarea
                    wire:model="lostReason"
                    label="Lý do thất bại *"
                    placeholder="VD: Đối thủ cạnh tranh giảm giá 20%, đối tác tạm hoãn ngân sách năm nay..."
                    rows="3"
                />
                @error('lostReason') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                <flux:button wire:click="$set('showLostModal', false)" variant="ghost" size="sm">
                    Hủy
                </flux:button>

                <flux:button wire:click="confirmCloseLost" variant="danger" size="sm">
                    Xác nhận Thất bại
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Modal Xác nhận Chốt Won -->
    <div
        x-data="{ open: @entangle('showWonModal') }"
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
            class="w-full max-w-md space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900"
        >
            <div>
                <h3 class="text-base font-semibold text-slate-950 dark:text-white">Chốt thắng cơ hội</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Xác nhận ghi nhận thương vụ thành công.</p>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bạn có chắc chắn muốn chốt thành công cơ hội <strong class="text-slate-900 dark:text-white">{{ $opportunity->title }}</strong> với giá trị dự kiến {{ number_format((float) $opportunity->amount, 0, ',', '.') }} VNĐ không?
            </p>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                <flux:button wire:click="$set('showWonModal', false)" variant="ghost" size="sm">
                    Hủy
                </flux:button>
                <flux:button wire:click="confirmCloseWon" variant="primary" color="emerald" size="sm">
                    Xác nhận chốt thắng
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Modal Xác nhận Mở lại Cơ hội -->
    <div
        x-data="{ open: @entangle('showReopenModal') }"
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
            class="w-full max-w-md space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900"
        >
            <div>
                <h3 class="text-base font-semibold text-slate-950 dark:text-white">Mở lại cơ hội bán hàng</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Khôi phục cơ hội về trạng thái đang mở.</p>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bạn có chắc chắn muốn mở lại cơ hội bán hàng <strong class="text-slate-900 dark:text-white">{{ $opportunity->title }}</strong> không?
            </p>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                <flux:button wire:click="$set('showReopenModal', false)" variant="ghost" size="sm">
                    Hủy
                </flux:button>
                <flux:button wire:click="confirmReopen" variant="primary" size="sm">
                    Xác nhận mở lại
                </flux:button>
            </div>
        </div>
    </div>
</div>
