<div class="space-y-6">
    @include('livewire.reports.partials.header', [
        'title' => 'Giám sát & Chất lượng Dữ liệu CRM',
        'description' => 'Phát hiện rủi ro dữ liệu kém chất lượng (thiếu thông tin, nghi trùng lặp, bỏ ngâm không chăm sóc, bản ghi mồ côi).',
        'hideNav' => true,
    ])

    @include('livewire.reports.partials.filters')

    @php
        $overview = $this->overviewMetrics;
        $incomplete = $this->incompleteRecords;
        $duplicates = $this->duplicateCandidates;
        $stale = $this->staleRecords;
        $orphans = $this->orphanRecords;

        $healthScore = $overview['health_score'];
        $healthColor = $healthScore >= 80 ? 'emerald' : ($healthScore >= 50 ? 'amber' : 'red');
    @endphp

    <!-- Health Score Banner & Audit KPI Cards -->
    <section class="crm-card space-y-4">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Điểm sức khỏe Dữ liệu CRM (Data Health Score)</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Tính dựa trên tỷ lệ dữ liệu chuẩn hóa trên tổng số {{ number_format($overview['total_records']) }} bản ghi dữ liệu.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $healthScore }}%</span>
                <flux:badge :color="$healthColor" size="sm">
                    {{ $healthScore >= 80 ? 'Dữ liệu Sạch' : ($healthScore >= 50 ? 'Cần Cải thiện' : 'Rủi ro Cao') }}
                </flux:badge>
            </div>
        </div>

        <!-- Health Progress Bar -->
        <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
            <div
                class="h-full rounded-full bg-emerald-600 transition-all duration-500 dark:bg-emerald-500"
                style="width: {{ $healthScore }}%"
            ></div>
        </div>

        <div class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-2 xl:grid-cols-4">
            <div
                wire:click="$set('activeTab', 'incomplete')"
                class="crm-card cursor-pointer transition hover:border-slate-400 dark:hover:border-slate-600 {{ $activeTab === 'incomplete' ? 'border-2 border-slate-900 dark:border-slate-100 shadow-sm' : '' }}"
            >
                <p class="text-xs text-slate-500 dark:text-slate-400">1. Thiếu thông tin liên hệ</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $overview['incomplete_count'] }} bản ghi</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lead, Contact, Company thiếu MST/SĐT/Email</p>
            </div>

            <div
                wire:click="$set('activeTab', 'duplicate')"
                class="crm-card cursor-pointer transition hover:border-slate-400 dark:hover:border-slate-600 {{ $activeTab === 'duplicate' ? 'border-2 border-slate-900 dark:border-slate-100 shadow-sm' : '' }}"
            >
                <p class="text-xs text-slate-500 dark:text-slate-400">2. Nghi trùng lặp dữ liệu</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $overview['duplicate_count'] }} cặp nghi trùng</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cặp bản ghi trùng tên/SĐT/Email chưa gộp</p>
            </div>

            <div
                wire:click="$set('activeTab', 'stale')"
                class="crm-card cursor-pointer transition hover:border-slate-400 dark:hover:border-slate-600 {{ $activeTab === 'stale' ? 'border-2 border-slate-900 dark:border-slate-100 shadow-sm' : '' }}"
            >
                <p class="text-xs text-slate-500 dark:text-slate-400">3. Bỏ ngâm / Chưa gán</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $overview['stale_count'] }} bản ghi</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lead chưa gán hoặc Cơ hội ngâm > 30 ngày</p>
            </div>

            <div
                wire:click="$set('activeTab', 'orphan')"
                class="crm-card cursor-pointer transition hover:border-slate-400 dark:hover:border-slate-600 {{ $activeTab === 'orphan' ? 'border-2 border-slate-900 dark:border-slate-100 shadow-sm' : '' }}"
            >
                <p class="text-xs text-slate-500 dark:text-slate-400">4. Bản ghi mồ côi</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $overview['orphan_count'] }} bản ghi</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Contact/Opportunity chưa gắn Doanh nghiệp</p>
            </div>
        </div>
    </section>

    <!-- Tab Section & Details Table -->
    <section class="crm-card space-y-4">
        <div class="border-b border-slate-200 dark:border-slate-800">
            <nav class="-mb-px flex space-x-6">
                <button
                    wire:click="$set('activeTab', 'incomplete')"
                    class="py-3 text-sm font-medium transition border-b-2 {{ $activeTab === 'incomplete' ? 'border-slate-900 text-slate-900 dark:border-slate-100 dark:text-white' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                >
                    Hồ sơ thiếu thông tin ({{ count($incomplete) }})
                </button>
                <button
                    wire:click="$set('activeTab', 'duplicate')"
                    class="py-3 text-sm font-medium transition border-b-2 {{ $activeTab === 'duplicate' ? 'border-slate-900 text-slate-900 dark:border-slate-100 dark:text-white' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                >
                    Cặp nghi trùng lặp ({{ count($duplicates) }})
                </button>
                <button
                    wire:click="$set('activeTab', 'stale')"
                    class="py-3 text-sm font-medium transition border-b-2 {{ $activeTab === 'stale' ? 'border-slate-900 text-slate-900 dark:border-slate-100 dark:text-white' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                >
                    Bản ghi chưa chăm sóc ({{ count($stale) }})
                </button>
                <button
                    wire:click="$set('activeTab', 'orphan')"
                    class="py-3 text-sm font-medium transition border-b-2 {{ $activeTab === 'orphan' ? 'border-slate-900 text-slate-900 dark:border-slate-100 dark:text-white' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                >
                    Bản ghi mồ côi ({{ count($orphans) }})
                </button>
            </nav>
        </div>

        <!-- Tab 1: Incomplete Records -->
        @if ($activeTab === 'incomplete')
            @if (empty($incomplete))
                <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center dark:border-slate-700">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Không có hồ sơ nào bị thiếu thông tin liên lạc hay mã số thuế.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Loại dữ liệu</th>
                                <th class="px-4 py-3">Tên bản ghi</th>
                                <th class="px-4 py-3">Người phụ trách</th>
                                <th class="px-4 py-3">Thiếu thông tin</th>
                                <th class="px-4 py-3 text-right">Xử lý nhanh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($incomplete as $item)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3.5"><flux:badge color="zinc" size="sm">{{ $item['type_label'] }}</flux:badge></td>
                                    <td class="px-4 py-3.5 font-medium text-slate-900 dark:text-white">{{ $item['name'] }}</td>
                                    <td class="px-4 py-3.5">{{ $item['owner_name'] }}</td>
                                    <td class="px-4 py-3.5"><flux:badge color="zinc" size="sm">{{ $item['missing_field'] }}</flux:badge></td>
                                    <td class="px-4 py-3.5 text-right">
                                        <flux:button :href="$item['url']" wire:navigate size="xs" variant="filled">Bổ sung ngay ➔</flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

        <!-- Tab 2: Duplicate Candidates -->
        @if ($activeTab === 'duplicate')
            @if (empty($duplicates))
                <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center dark:border-slate-700">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Không phát hiện cặp dữ liệu nào bị nghi trùng lặp.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Loại đối tượng</th>
                                <th class="px-4 py-3">Bản ghi gốc</th>
                                <th class="px-4 py-3">Bản ghi nghi trùng</th>
                                <th class="px-4 py-3">Trùng theo chỉ số</th>
                                <th class="px-4 py-3 text-right">Công cụ gộp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($duplicates as $dupe)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3.5"><flux:badge color="zinc" size="sm">{{ $dupe['type_label'] }}</flux:badge></td>
                                    <td class="px-4 py-3.5 font-medium text-slate-900 dark:text-white">{{ $dupe['target_name'] }}</td>
                                    <td class="px-4 py-3.5 font-medium text-slate-900 dark:text-white">{{ $dupe['duplicate_name'] }}</td>
                                    <td class="px-4 py-3.5"><flux:badge color="zinc" size="sm">{{ $dupe['matched_reason'] }}</flux:badge></td>
                                    <td class="px-4 py-3.5 text-right">
                                        <flux:button :href="$dupe['url']" wire:navigate size="xs" variant="filled">Mở công cụ gộp ➔</flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

        <!-- Tab 3: Stale Records -->
        @if ($activeTab === 'stale')
            @if (empty($stale))
                <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center dark:border-slate-700">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Không có bản ghi nào bị ngâm trệch hoặc chưa được gán người phụ trách.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Loại dữ liệu</th>
                                <th class="px-4 py-3">Tên bản ghi</th>
                                <th class="px-4 py-3">Người phụ trách</th>
                                <th class="px-4 py-3">Lý do ngâm trệch</th>
                                <th class="px-4 py-3 text-right">Xử lý ngay</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($stale as $item)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3.5"><flux:badge color="zinc" size="sm">{{ $item['type_label'] }}</flux:badge></td>
                                    <td class="px-4 py-3.5 font-medium text-slate-900 dark:text-white">{{ $item['name'] }}</td>
                                    <td class="px-4 py-3.5">{{ $item['owner_name'] }}</td>
                                    <td class="px-4 py-3.5"><flux:badge color="zinc" size="sm">{{ $item['stale_reason'] }}</flux:badge></td>
                                    <td class="px-4 py-3.5 text-right">
                                        <flux:button :href="$item['url']" wire:navigate size="xs" variant="filled">Mở chăm sóc ➔</flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

        <!-- Tab 4: Orphan Records -->
        @if ($activeTab === 'orphan')
            @if (empty($orphans))
                <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center dark:border-slate-700">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tất cả Người liên hệ và Cơ hội bán hàng đều đã được gắn đúng Doanh nghiệp / Khách hàng.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Loại dữ liệu</th>
                                <th class="px-4 py-3">Tên bản ghi</th>
                                <th class="px-4 py-3">Người phụ trách</th>
                                <th class="px-4 py-3">Lý do mồ côi</th>
                                <th class="px-4 py-3 text-right">Gắn khách hàng</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($orphans as $item)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3.5"><flux:badge color="zinc" size="sm">{{ $item['type_label'] }}</flux:badge></td>
                                    <td class="px-4 py-3.5 font-medium text-slate-900 dark:text-white">{{ $item['name'] }}</td>
                                    <td class="px-4 py-3.5">{{ $item['owner_name'] }}</td>
                                    <td class="px-4 py-3.5"><flux:badge color="zinc" size="sm">{{ $item['orphan_reason'] }}</flux:badge></td>
                                    <td class="px-4 py-3.5 text-right">
                                        <flux:button :href="$item['url']" wire:navigate size="xs" variant="filled">Gắn KH ➔</flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </section>

    <!-- Drill-Down Modal -->
    <livewire:reports.report-drill-down-modal />
</div>
