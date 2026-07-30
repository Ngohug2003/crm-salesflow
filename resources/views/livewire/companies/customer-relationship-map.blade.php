<div class="space-y-6">
    @php
        $company = $this->mapData['company'];
        $contacts = $this->mapData['contacts'];
        $opportunities = $this->mapData['opportunities'];
        $convertedLeads = $this->mapData['converted_leads'];
        $recentActivities = $this->mapData['recent_activities'];
        $summary = $this->mapData['summary'];
    @endphp

    <!-- Tóm tắt chỉ số mối quan hệ -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Người liên hệ</span>
                <flux:badge color="blue" size="sm">{{ $summary['total_contacts'] }} người</flux:badge>
            </div>
            <p class="mt-2 text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $summary['total_contacts'] }} người</p>
            <p class="mt-1 text-xs text-slate-400">Đại diện làm việc thuộc doanh nghiệp</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Cơ hội bán hàng</span>
                <flux:badge color="indigo" size="sm">{{ $summary['total_opportunities'] }} cơ hội</flux:badge>
            </div>
            <p class="mt-2 text-xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400">
                {{ number_format($summary['open_pipeline'], 0, ',', '.') }} ₫
            </p>
            <p class="mt-1 text-xs text-slate-400">{{ $summary['won_opportunities'] }} cơ hội đã thành công</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Lead nguồn</span>
                <flux:badge color="emerald" size="sm">{{ $summary['total_converted_leads'] }} Lead</flux:badge>
            </div>
            <p class="mt-2 text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ $summary['total_converted_leads'] > 0 ? 'Chuyển đổi từ Lead' : 'Tạo trực tiếp' }}
            </p>
            <p class="mt-1 text-xs text-slate-400">Nguồn gốc dữ liệu đầu vào</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wider text-slate-500">Trạng thái SLA</span>
                <flux:badge :color="$company['sla_info']['color']" size="sm">{{ $company['sla_info']['label'] }}</flux:badge>
            </div>
            <p class="mt-2 text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                Hạn {{ $company['sla_info']['target_hours'] }}h
            </p>
            <p class="mt-1 text-xs text-slate-400">Chu kỳ chăm sóc khách hàng</p>
        </div>
    </div>

    <!-- Sơ đồ cây quan hệ -->
    <div class="space-y-6">
        <!-- Nút Doanh nghiệp -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Doanh nghiệp chính</span>
                        <flux:badge :color="$company['sla_info']['color']" size="sm">{{ $company['sla_info']['label'] }}</flux:badge>
                    </div>
                    <h4 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ $company['name'] }}</h4>
                    <p class="mt-1 text-xs text-slate-500">
                        Mã số thuế: <span class="font-mono text-slate-700 dark:text-slate-300">{{ $company['tax_code'] ?: '—' }}</span> | Ngành nghề: <span class="text-slate-700 dark:text-slate-300">{{ $company['industry'] ?: '—' }}</span> | Người phụ trách: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $company['owner_name'] }}</span> ({{ $company['department_name'] }})
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <flux:button :href="route('companies.show', $company['id'])" wire:navigate size="sm" variant="outline" icon="building-office">Xem doanh nghiệp</flux:button>
                </div>
            </div>
        </div>

        <!-- Các nhánh liên kết -->
        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Nhánh 1: Người liên hệ -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h5 class="font-semibold text-slate-900 dark:text-white text-sm">Người liên hệ</h5>
                    <flux:badge color="blue" size="sm">{{ count($contacts) }}</flux:badge>
                </div>

                @if ($contacts === [])
                    <div class="py-6 text-center text-xs text-slate-400">
                        Chưa có người liên hệ thuộc doanh nghiệp này.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($contacts as $contact)
                            <div class="rounded-lg border border-slate-100 bg-slate-50/70 p-3 text-xs dark:border-slate-800/80 dark:bg-slate-950/40">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $contact['full_name'] }}</span>
                                    @if ($contact['is_primary'])
                                        <flux:badge color="emerald" size="sm">Liên hệ chính</flux:badge>
                                    @endif
                                </div>
                                <p class="mt-1 text-slate-500">{{ $contact['job_title'] ?: 'Chưa có chức danh' }}</p>
                                <div class="mt-2 flex flex-col gap-1 text-slate-500">
                                    <span>Email: {{ $contact['email'] ?: 'Chưa có' }}</span>
                                    <span>SĐT: {{ $contact['phone'] ?: 'Chưa có' }}</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between border-t border-slate-200/60 pt-2 text-[11px] text-slate-400 dark:border-slate-800">
                                    <span>Phụ trách: {{ $contact['owner_name'] }}</span>
                                    <flux:button :href="route('contacts.show', $contact['id'])" wire:navigate size="sm" variant="ghost">Chi tiết</flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Nhánh 2: Cơ hội bán hàng -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h5 class="font-semibold text-slate-900 dark:text-white text-sm">Cơ hội bán hàng</h5>
                    <flux:badge color="indigo" size="sm">{{ count($opportunities) }}</flux:badge>
                </div>

                @if ($opportunities === [])
                    <div class="py-6 text-center text-xs text-slate-400">
                        Chưa có cơ hội bán hàng nào liên kết.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($opportunities as $opp)
                            <div class="rounded-lg border border-slate-100 bg-slate-50/70 p-3 text-xs dark:border-slate-800/80 dark:bg-slate-950/40">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-900 dark:text-white truncate max-w-[12rem]">{{ $opp['title'] }}</span>
                                    @if ($opp['is_won'])
                                        <flux:badge color="emerald" size="sm">Thành công</flux:badge>
                                    @elseif($opp['is_lost'])
                                        <flux:badge color="red" size="sm">Thất bại</flux:badge>
                                    @else
                                        <flux:badge color="indigo" size="sm">{{ $opp['stage_name'] }}</flux:badge>
                                    @endif
                                </div>
                                <div class="mt-2 flex items-center justify-between font-medium text-slate-700 dark:text-slate-300">
                                    <span>Giá trị: {{ number_format($opp['amount'], 0, ',', '.') }} ₫</span>
                                    <span class="text-[11px] text-slate-400">Đóng: {{ $opp['expected_close_date'] ?: '—' }}</span>
                                </div>
                                @if ($opp['contact_name'])
                                    <p class="mt-1 text-[11px] text-slate-500">Thông qua: {{ $opp['contact_name'] }}</p>
                                @endif
                                <div class="mt-2 flex items-center justify-between border-t border-slate-200/60 pt-2 text-[11px] text-slate-400 dark:border-slate-800">
                                    <span>Phụ trách: {{ $opp['owner_name'] }}</span>
                                    <flux:button :href="route('opportunities.show', $opp['id'])" wire:navigate size="sm" variant="ghost">Chi tiết</flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Nhánh 3: Lead nguồn -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h5 class="font-semibold text-slate-900 dark:text-white text-sm">Lead nguồn đã chuyển đổi</h5>
                    <flux:badge color="emerald" size="sm">{{ count($convertedLeads) }}</flux:badge>
                </div>

                @if ($convertedLeads === [])
                    <div class="py-6 text-center text-xs text-slate-400">
                        Khách hàng được tạo trực tiếp (không qua chuyển đổi Lead).
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($convertedLeads as $lead)
                            <div class="rounded-lg border border-slate-100 bg-slate-50/70 p-3 text-xs dark:border-slate-800/80 dark:bg-slate-950/40">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $lead['full_name'] }}</span>
                                    <flux:badge :color="$lead['score_badge_color']" size="sm">{{ $lead['score'] }}đ — {{ $lead['score_level_label'] }}</flux:badge>
                                </div>
                                <p class="mt-1 text-slate-500">Nguồn: {{ $lead['source_name'] }}</p>
                                <div class="mt-2 flex items-center justify-between border-t border-slate-200/60 pt-2 text-[11px] text-slate-400 dark:border-slate-800">
                                    <span>Ngày chuyển: {{ $lead['converted_at'] ?: '—' }}</span>
                                    <flux:button :href="route('leads.show', $lead['id'])" wire:navigate size="sm" variant="ghost">Lead chi tiết</flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Nhánh 4: Lịch sử tương tác gần nhất -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                <h5 class="font-semibold text-slate-900 dark:text-white text-sm">Lịch sử tương tác gần nhất</h5>
                <span class="text-xs text-slate-400">Top 5 hoạt động gần đây</span>
            </div>

            @if ($recentActivities === [])
                <div class="py-4 text-center text-xs text-slate-400">
                    Chưa có lịch sử tương tác nào được ghi nhận.
                </div>
            @else
                <div class="relative border-l border-slate-200 ml-3 space-y-4 pl-4 dark:border-slate-800">
                    @foreach ($recentActivities as $act)
                        <div class="relative">
                            <span class="absolute -left-[21px] top-1 size-2 rounded-full bg-slate-400 ring-4 ring-white dark:ring-slate-900"></span>
                            <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-center">
                                <p class="text-xs font-medium text-slate-900 dark:text-white">{{ $act['description'] }}</p>
                                <span class="text-[11px] text-slate-400">{{ $act['created_at'] }} | bởi {{ $act['causer_name'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
