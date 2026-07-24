<div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $opportunity->title }}</h1>

                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold" style="background-color: {{ $opportunity->stage?->color }}20; color: {{ $opportunity->stage?->color }}; border: 1px solid {{ $opportunity->stage?->color }}40;">
                    <span class="size-2 rounded-full" style="background-color: {{ $opportunity->stage?->color }};"></span>
                    {{ $opportunity->stage?->name }} ({{ $opportunity->stage?->probability }}%)
                </span>

                @if ($opportunity->is_won)
                    <flux:badge variant="solid" color="emerald" size="sm">Chốt thành công (Won)</flux:badge>
                @elseif ($opportunity->is_lost)
                    <flux:badge variant="solid" color="red" size="sm">Thất bại (Lost)</flux:badge>
                @endif
            </div>

            <p class="mt-1 text-sm text-slate-500">Mã cơ hội: <code class="font-mono text-slate-700 dark:text-slate-300">{{ $opportunity->code }}</code></p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button href="{{ route('opportunities.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Danh sách
            </flux:button>

            @if (! $opportunity->is_won && ! $opportunity->is_lost)
                <flux:button wire:click="closeWon" wire:confirm="Bạn có chắc chắn muốn CHỐT THÀNH CÔNG cơ hội bán hàng này không?" variant="primary" color="emerald" icon="check-circle" size="sm">
                    Chốt Won
                </flux:button>

                <flux:button wire:click="$set('showLostModal', true)" variant="danger" icon="x-circle" size="sm">
                    Báo Lost
                </flux:button>
            @else
                <flux:button wire:click="reopen" wire:confirm="Bạn có chắc chắn muốn MỞ LẠI cơ hội bán hàng này không?" variant="filled" icon="arrow-path" size="sm">
                    Mở lại Cơ hội
                </flux:button>
            @endif

            @can('update', $opportunity)
                <flux:button href="{{ route('opportunities.edit', $opportunity->id) }}" variant="subtle" icon="pencil" size="sm">
                    Sửa
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    @error('stage_error')
        <div class="rounded-lg bg-red-50 p-4 text-sm font-semibold text-red-800 dark:bg-red-950/40 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    <!-- Pipeline Stage Progress Bar -->
    <div class="crm-card">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tiến trình Quy trình: {{ $opportunity->pipeline?->name }}</h2>
            <span class="text-xs text-slate-400">Nhấp vào giai đoạn bên dưới để chuyển Stage nhanh</span>
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pb-2">
            @foreach ($opportunity->pipeline?->stages ?? [] as $stg)
                @php
                    $isCurrent = $stg->id === $opportunity->stage_id;
                    $isPassed = $stg->position < ($opportunity->stage?->position ?? 0);
                @endphp
                <button
                    type="button"
                    wire:click="changeStage({{ $stg->id }})"
                    wire:confirm="Bạn có chắc chắn muốn chuyển Cơ hội bán hàng sang giai đoạn '{{ $stg->name }}' không?"
                    class="flex flex-1 min-w-[140px] flex-col gap-1.5 rounded-lg border p-2.5 text-left transition-all hover:scale-[1.02] {{ $isCurrent ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/30 ring-2 ring-indigo-500/20' : ($isPassed ? 'border-emerald-300 bg-emerald-50/30 dark:border-emerald-900/50 dark:bg-emerald-950/20' : 'border-slate-200 bg-slate-50/50 hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900/50') }}"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold {{ $isCurrent ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-400' }}">
                            {{ $stg->position }}. {{ $stg->name }}
                        </span>
                        <span class="text-[10px] font-semibold text-slate-500">{{ $stg->probability }}%</span>
                    </div>

                    <div class="h-1.5 w-full rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                        <div class="h-full transition-all" style="width: {{ $stg->probability }}%; background-color: {{ $stg->color }};"></div>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left 2-Columns: Files & Timeline -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Tệp đính kèm -->
            <livewire:customers.customer-attachment-manager :modelType="App\Models\Opportunity::class" :modelId="$opportunity->id" />

            <!-- Nhật ký & Timeline -->
            <livewire:customers.customer-timeline-feed :modelType="App\Models\Opportunity::class" :modelId="$opportunity->id" />
        </div>

        <!-- Right 1-Column: Thống kê & Thông tin thương mại -->
        <div class="space-y-6">
            <div class="crm-card space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Thông tin Thương mại</h3>

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
                        <strong class="text-base text-indigo-600 dark:text-indigo-400">{{ number_format($opportunity->weighted_value) }} đ</strong>
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
                            <a href="{{ route('companies.show', $opportunity->company->id) }}" class="block font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                {{ $opportunity->company->name }}
                            </a>
                        @else
                            <p class="font-medium text-slate-400 italic">Chưa gắn Doanh nghiệp</p>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-500">Người liên hệ:</span>
                        @if ($opportunity->contact)
                            <a href="{{ route('contacts.show', $opportunity->contact->id) }}" class="block font-medium text-indigo-600 hover:underline dark:text-indigo-400">
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
            </div>

            @if ($opportunity->notes)
                <div class="crm-card space-y-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Ghi chú chi tiết</h3>
                    <p class="text-xs leading-relaxed text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $opportunity->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Nhập Lý do Thất bại (Close Lost) -->
    @if ($showLostModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Báo Thất bại Cơ hội bán hàng</h3>
                    <button wire:click="$set('showLostModal', false)" type="button" class="text-slate-400 hover:text-slate-600">
                        <flux:icon.x-mark class="size-5" />
                    </button>
                </div>

                <p class="text-xs text-slate-500">Vui lòng cung cấp lý do thất bại để hoàn tất đóng cơ hội này.</p>

                <div>
                    <flux:textarea
                        wire:model="lostReason"
                        label="Lý do thất bại *"
                        placeholder="VD: Đối thủ cạnh tranh giảm giá 20%, đối tác tạm hoãn ngân sách năm nay..."
                        rows="3"
                    />
                    @error('lostReason') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <flux:button wire:click="$set('showLostModal', false)" variant="ghost" size="sm">
                        Hủy
                    </flux:button>

                    <flux:button wire:click="confirmCloseLost" variant="danger" size="sm">
                        Xác nhận Thất bại
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
