<div class="space-y-6">
    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Trang chủ</a>
                <span>/</span>
                <a href="{{ route('opportunities.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>Cơ hội bán hàng</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Cảnh báo Deal Rủi ro</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Cảnh báo Deal Rủi ro (At-Risk Deals)</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
                Theo dõi, phát hiện sớm nguy cơ trượt cơ hội bán hàng và ghi nhận phương án xử lý trước ngày chốt đơn.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="primary" icon="arrow-path" wire:click="recalculateAll">
                Đánh giá lại toàn bộ
            </flux:button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('success') }}
        </div>
    @endif

    {{-- Quiet Metric Summary Cards --}}
    <div class="grid gap-4 sm:grid-cols-4">
        <button
            type="button"
            wire:click="$set('selectedLevel', 'critical')"
            class="crm-card p-4 text-left transition hover:border-rose-300 dark:hover:border-rose-700 {{ $selectedLevel === 'critical' ? 'ring-2 ring-rose-500' : '' }}"
        >
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-rose-600 dark:text-rose-400">Nguy hiểm</span>
                <flux:badge color="rose" size="sm">Critical</flux:badge>
            </div>
            <p class="mt-2 text-2xl font-semibold text-rose-700 dark:text-rose-300">{{ $criticalCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Score 76 - 100</p>
        </button>

        <button
            type="button"
            wire:click="$set('selectedLevel', 'high')"
            class="crm-card p-4 text-left transition hover:border-amber-300 dark:hover:border-amber-700 {{ $selectedLevel === 'high' ? 'ring-2 ring-amber-500' : '' }}"
        >
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Rủi ro cao</span>
                <flux:badge color="amber" size="sm">High</flux:badge>
            </div>
            <p class="mt-2 text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ $highCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Score 51 - 75</p>
        </button>

        <button
            type="button"
            wire:click="$set('selectedLevel', 'medium')"
            class="crm-card p-4 text-left transition hover:border-yellow-300 dark:hover:border-yellow-700 {{ $selectedLevel === 'medium' ? 'ring-2 ring-yellow-500' : '' }}"
        >
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-yellow-600 dark:text-yellow-400">Trung bình</span>
                <flux:badge color="yellow" size="sm">Medium</flux:badge>
            </div>
            <p class="mt-2 text-2xl font-semibold text-yellow-700 dark:text-yellow-300">{{ $mediumCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Score 26 - 50</p>
        </button>

        <button
            type="button"
            wire:click="$set('selectedLevel', 'all')"
            class="crm-card p-4 text-left transition hover:border-indigo-300 dark:hover:border-indigo-700 {{ $selectedLevel === 'all' ? 'ring-2 ring-indigo-500' : '' }}"
        >
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tất cả Deals</span>
                <flux:badge color="zinc" size="sm">Tất cả</flux:badge>
            </div>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $totalCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Đang mở</p>
        </button>
    </div>

    {{-- Main Data Section --}}
    <section class="crm-card relative" aria-labelledby="at-risk-deals-title">
        <div class="data-list-heading flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 p-4 dark:border-slate-800">
            <div>
                <h2 id="at-risk-deals-title" class="font-semibold text-slate-950 dark:text-white">Danh sách Cơ hội rủi ro</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Có {{ $opportunities->total() }} Cơ hội bán hàng thuộc phạm vi rủi ro.</p>
            </div>
            @if ($search !== '' || $selectedDepartmentId !== null || $selectedLevel !== 'all')
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">Xóa bộ lọc</flux:button>
            @endif
        </div>

        {{-- Filters Toolbar --}}
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                label="Tìm kiếm"
                placeholder="Tìm tên deal, mã..."
                icon="magnifying-glass"
            />

            <x-forms.smart-select wire:model.live="selectedDepartmentId" label="Phòng ban">
                <option value="">Tất cả phòng ban</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </x-forms.smart-select>

            <x-forms.smart-select wire:model.live="selectedLevel" label="Mức độ rủi ro">
                <option value="all">Tất cả mức độ</option>
                <option value="critical">Nguy hiểm (Critical)</option>
                <option value="high">Rủi ro cao (High)</option>
                <option value="medium">Trung bình (Medium)</option>
                <option value="low">An toàn / Thấp (Low)</option>
            </x-forms.smart-select>
        </div>

        {{-- Table view --}}
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Mã & Tên Deal</flux:table.column>
                    <flux:table.column>Người phụ trách</flux:table.column>
                    <flux:table.column>Giá trị / Hạn chốt</flux:table.column>
                    <flux:table.column>Điểm rủi ro</flux:table.column>
                    <flux:table.column>Tín hiệu rủi ro & Khuyến nghị</flux:table.column>
                    <flux:table.column>Trạng thái xử lý</flux:table.column>
                    <flux:table.column align="end">Thao tác</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($opportunities as $opp)
                        @php
                            $snapshot = $opp->currentRiskSnapshot;
                            $score = $snapshot?->score ?? 0;
                            $level = $snapshot?->level ?? 'low';

                            $badgeColor = match ($level) {
                                'critical' => 'rose',
                                'high' => 'amber',
                                'medium' => 'yellow',
                                default => 'emerald',
                            };

                            $levelLabel = match ($level) {
                                'critical' => 'Nguy hiểm',
                                'high' => 'Rủi ro cao',
                                'medium' => 'Trung bình',
                                default => 'An toàn',
                            };

                            $ack = $snapshot?->latestAcknowledgement;
                        @endphp

                        <flux:table.row :key="$opp->id">
                            <flux:table.cell variant="strong">
                                <div>
                                    <a href="{{ route('opportunities.show', $opp->id) }}" wire:navigate class="font-semibold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                                        {{ $opp->title }}
                                    </a>
                                    <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                        <span class="font-mono text-indigo-600 dark:text-indigo-400">#{{ $opp->code }}</span>
                                        <span>•</span>
                                        <span>{{ $opp->company?->name ?: 'Khách hàng cá nhân' }}</span>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="text-xs">
                                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ $opp->owner?->name ?: 'Chưa phân công' }}</span>
                                    <div class="text-slate-400">{{ $opp->department?->name ?: 'N/A' }}</div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="text-xs">
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ number_format((float) $opp->amount, 0, ',', '.') }} ₫</div>
                                    <div class="{{ $opp->expected_close_date && \Illuminate\Support\Carbon::parse($opp->expected_close_date)->lt(now()) ? 'text-rose-600 font-semibold' : 'text-slate-500' }}">
                                        {{ $opp->expected_close_date ? \Illuminate\Support\Carbon::parse($opp->expected_close_date)->format('d/m/Y') : 'Chưa đặt' }}
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:badge color="{{ $badgeColor }}" size="sm" class="font-bold">
                                        {{ $score }}đ
                                    </flux:badge>
                                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ $levelLabel }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($snapshot && !empty($snapshot->factors))
                                    <div class="max-w-md space-y-1.5 text-xs">
                                        @foreach ($snapshot->factors as $factor)
                                            <div class="rounded border border-slate-100 bg-slate-50 p-1.5 dark:border-slate-800 dark:bg-slate-900/50">
                                                <span class="font-medium text-slate-800 dark:text-slate-200">• {{ $factor->title }}</span>
                                                @if ($factor->recommended_action)
                                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Khuyến nghị: {{ $factor->recommended_action }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Không có tín hiệu rủi ro</span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($ack)
                                    <div class="text-xs">
                                        <flux:badge color="{{ $ack->status === 'resolved' ? 'emerald' : ($ack->status === 'in_progress' ? 'amber' : 'zinc') }}" size="sm">
                                            {{ $ack->status === 'resolved' ? 'Đã giải quyết' : ($ack->status === 'in_progress' ? 'Đang xử lý' : 'Đã ghi nhận') }}
                                        </flux:badge>
                                        <div class="mt-1 text-[11px] text-slate-500">{{ $ack->user?->name }} • {{ $ack->acted_at?->format('d/m H:i') }}</div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Chưa ghi nhận</span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="subtle" icon="chat-bubble-bottom-center-text" wire:click="openAcknowledgeModal({{ $opp->id }})">
                                    Xử lý / Ghi chú
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colSpan="7" class="py-12 text-center text-slate-500">
                                <p class="font-medium">Không có Cơ hội bán hàng rủi ro phù hợp trong phạm vi.</p>
                                <p class="mt-1 text-xs text-slate-400">Tất cả các Deal đang hoạt động an toàn hoặc không khớp bộ lọc.</p>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $opportunities->links() }}
        </div>
    </section>

    {{-- Modal Acknowledge Risk --}}
    <flux:modal name="ack-risk-modal" wire:model="showAckModal" class="w-full max-w-lg">
        <form wire:submit.prevent="saveAcknowledgement" class="space-y-6">
            <div>
                <flux:heading size="lg">Ghi nhận xử lý Rủi ro Deal</flux:heading>
                <flux:subheading class="mt-1">Xác nhận đã xem hoặc cập nhật tiến độ xử lý cảnh báo cho Quản lý & Team.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Trạng thái xử lý</flux:label>
                    <x-forms.smart-select wire:model="ackStatus">
                        <option value="acknowledged">Đã ghi nhận (Acknowledged)</option>
                        <option value="in_progress">Đang khắc phục (In Progress)</option>
                        <option value="resolved">Đã giải quyết (Resolved)</option>
                    </x-forms.smart-select>
                    <flux:error name="ackStatus" />
                </flux:field>

                <flux:field>
                    <flux:label>Ghi chú / Phương án tác động</flux:label>
                    <flux:textarea wire:model="ackNotes" placeholder="Ví dụ: Đã gọi lại khách hàng hẹn cuộc họp demo vào thứ 6..." rows="4" />
                    <flux:error name="ackNotes" />
                </flux:field>
            </div>

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showAckModal', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary">Lưu ghi nhận</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
