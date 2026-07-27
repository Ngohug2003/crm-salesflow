<div>
    @php
        /** @var \App\Models\Company $company */
        $company = $data['company'];
    @endphp

    <!-- Header -->
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Doanh nghiệp / Góc nhìn 360°</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Hồ sơ 360° — {{ $company->name }}</h1>
            <p class="mt-2 max-w-3xl text-slate-500">
                Mã số thuế: <span class="font-mono text-slate-700 dark:text-slate-300">{{ $company->tax_code ?: '—' }}</span> | Ngành nghề: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $company->industry ?: '—' }}</span> | Người phụ trách: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $company->owner?->name ?: 'Chưa phân công' }}</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('companies.show', $company->id)" wire:navigate variant="ghost" icon="arrow-left">
                Hồ sơ chi tiết
            </flux:button>
            <flux:button :href="route('companies.index')" wire:navigate variant="outline" icon="building-office">
                Danh sách Doanh nghiệp
            </flux:button>
        </div>
    </div>

    <!-- 4 KPI Metrics Cards -->
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Doanh thu tích lũy</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                {{ number_format((float) $data['wonRevenue'], 0, ',', '.') }} ₫
            </p>
            <p class="mt-1 text-xs text-slate-400">Tổng doanh thu từ các cơ hội chốt thành công</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Pipeline đang mở</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400">
                {{ number_format((float) $data['openPipelineValue'], 0, ',', '.') }} ₫
            </p>
            <p class="mt-1 text-xs text-slate-400">{{ $data['opportunitiesCount'] }} cơ hội bán hàng đang chăm sóc</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Người liên hệ</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ $data['contactsCount'] }}
            </p>
            <p class="mt-1 text-xs text-slate-400">Đại diện giao tiếp thuộc doanh nghiệp</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Công việc tồn đọng</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
                {{ $data['openTasksCount'] }}
            </p>
            <p class="mt-1 text-xs text-slate-400">Nhiệm vụ cần thực hiện</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="mb-6 border-b border-slate-200 dark:border-slate-800">
        <nav class="-mb-px flex space-x-6 overflow-x-auto text-sm font-medium">
            <button
                type="button"
                wire:click="setTab('overview')"
                @class([
                    'whitespace-nowrap pb-3 border-b-2 transition',
                    'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400 font-semibold' => $activeTab === 'overview',
                    'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => $activeTab !== 'overview',
                ])
            >
                Tổng quan & Timeline 360°
            </button>
            <button
                type="button"
                wire:click="setTab('opportunities')"
                @class([
                    'whitespace-nowrap pb-3 border-b-2 transition',
                    'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400 font-semibold' => $activeTab === 'opportunities',
                    'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => $activeTab !== 'opportunities',
                ])
            >
                Cơ hội bán hàng ({{ $data['opportunitiesCount'] }})
            </button>
            <button
                type="button"
                wire:click="setTab('contacts')"
                @class([
                    'whitespace-nowrap pb-3 border-b-2 transition',
                    'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400 font-semibold' => $activeTab === 'contacts',
                    'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => $activeTab !== 'contacts',
                ])
            >
                Người liên hệ ({{ $data['contactsCount'] }})
            </button>
            <button
                type="button"
                wire:click="setTab('tasks')"
                @class([
                    'whitespace-nowrap pb-3 border-b-2 transition',
                    'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400 font-semibold' => $activeTab === 'tasks',
                    'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => $activeTab !== 'tasks',
                ])
            >
                Công việc ({{ count($data['tasks']) }})
            </button>
        </nav>
    </div>

    <!-- Tab Contents -->
    @if ($activeTab === 'overview')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left 2 Cols: Tóm tắt thông tin Doanh nghiệp & Timeline 360 -->
            <div class="space-y-6 lg:col-span-2">
                <section class="crm-card">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Thông tin tổng quan Doanh nghiệp</h3>
                    <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2 text-sm">
                        <div><dt class="text-slate-500">Tên doanh nghiệp:</dt><dd class="font-semibold text-slate-900 dark:text-white">{{ $company->name }}</dd></div>
                        <div><dt class="text-slate-500">Mã số thuế:</dt><dd class="font-mono text-slate-900 dark:text-white">{{ $company->tax_code ?: '—' }}</dd></div>
                        <div><dt class="text-slate-500">Ngành nghề:</dt><dd class="text-slate-900 dark:text-white">{{ $company->industry ?: '—' }}</dd></div>
                        <div><dt class="text-slate-500">Quy mô:</dt><dd class="text-slate-900 dark:text-white">{{ $company->company_size ?: '—' }}</dd></div>
                        <div><dt class="text-slate-500">Điện thoại / Email:</dt><dd class="text-slate-900 dark:text-white">{{ $company->phone ?: '—' }} / {{ $company->email ?: '—' }}</dd></div>
                        <div><dt class="text-slate-500">Website:</dt><dd class="text-slate-900 dark:text-white">{{ $company->website ?: '—' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-slate-500">Địa chỉ:</dt><dd class="text-slate-900 dark:text-white">{{ $company->address ?: '—' }}</dd></div>
                    </dl>
                </section>

                <section class="crm-card">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Dòng thời gian tích hợp 360° (Timeline)</h3>
                        <flux:badge color="zinc">{{ count($data['timeline']) }} nhật ký</flux:badge>
                    </div>

                    @if ($data['timeline']->isEmpty())
                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Chưa có lịch sử tương tác 360°</p>
                        </div>
                    @else
                        <ol class="relative mt-5 space-y-0 before:absolute before:bottom-3 before:left-[0.6875rem] before:top-3 before:w-px before:bg-slate-200 dark:before:bg-slate-800">
                            @foreach ($data['timeline'] as $item)
                                <li class="relative flex gap-4 pb-5 last:pb-0">
                                    <span @class([
                                        'relative z-10 mt-1 size-6 shrink-0 rounded-full border-4 border-white dark:border-slate-900',
                                        'bg-emerald-500' => $item['type'] === 'activity',
                                        'bg-purple-500' => $item['type'] === 'opportunity',
                                        'bg-amber-500' => $item['type'] === 'task',
                                    ])></span>
                                    <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                                        <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-start">
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ $item['title'] }}</p>
                                            <time class="shrink-0 text-xs text-slate-400">
                                                {{ $item['occurredAt']->timezone(config('crm.display_timezone', 'Asia/Ho_Chi_Minh'))->format('d/m/Y H:i:s') }}
                                            </time>
                                        </div>
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ $item['description'] }}</p>
                                        <p class="mt-2 text-xs text-slate-400">Thực hiện bởi: <span class="font-medium text-slate-600 dark:text-slate-300">{{ $item['actorName'] }}</span></p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            </div>

            <!-- Right 1 Col: Quick Links & Summary Lists -->
            <div class="space-y-6">
                <!-- Contacts Summary -->
                <section class="crm-card">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                        <h3 class="font-semibold text-slate-900 dark:text-white">Người liên hệ chính</h3>
                        <button type="button" wire:click="setTab('contacts')" class="text-xs text-emerald-600 hover:underline dark:text-emerald-400">Xem tất cả ({{ $data['contactsCount'] }})</button>
                    </div>

                    <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($data['contacts']->take(3) as $contact)
                            <div class="py-2 text-sm">
                                <p class="font-medium text-slate-900 dark:text-white flex items-center justify-between">
                                    <span>{{ $contact->full_name }}</span>
                                    @if ($contact->is_primary)
                                        <flux:badge color="emerald" size="sm">Đại diện</flux:badge>
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $contact->job_title ?: 'Chức vụ không rõ' }} — {{ $contact->phone ?: $contact->email }}</p>
                            </div>
                        @empty
                            <p class="py-3 text-xs text-slate-500">Chưa có người liên hệ nào.</p>
                        @endforelse
                    </div>
                </section>

                <!-- Opportunities Summary -->
                <section class="crm-card">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                        <h3 class="font-semibold text-slate-900 dark:text-white">Cơ hội bán hàng gần đây</h3>
                        <button type="button" wire:click="setTab('opportunities')" class="text-xs text-emerald-600 hover:underline dark:text-emerald-400">Xem tất cả ({{ $data['opportunitiesCount'] }})</button>
                    </div>

                    <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($data['opportunities']->take(3) as $opp)
                            <div class="py-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <p class="font-medium text-slate-900 dark:text-white truncate">{{ $opp->title }}</p>
                                    <flux:badge size="sm" :color="$opp->stage?->is_won ? 'emerald' : ($opp->stage?->is_lost ? 'red' : 'indigo')">
                                        {{ $opp->stage?->name ?? 'Giai đoạn' }}
                                    </flux:badge>
                                </div>
                                <p class="text-xs text-slate-500 mt-1 font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ number_format((float) $opp->amount, 0, ',', '.') }} ₫
                                </p>
                            </div>
                        @empty
                            <p class="py-3 text-xs text-slate-500">Chưa có cơ hội bán hàng nào.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @elseif ($activeTab === 'opportunities')
        <div class="crm-card">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Danh sách Cơ hội bán hàng (Opportunities)</h3>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Tên Cơ hội</flux:table.column>
                        <flux:table.column>Quy trình / Giai đoạn</flux:table.column>
                        <flux:table.column align="end">Giá trị dự kiến</flux:table.column>
                        <flux:table.column>Người phụ trách</flux:table.column>
                        <flux:table.column align="end">Thao tác</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($data['opportunities'] as $opp)
                            <flux:table.row :key="$opp->id">
                                <flux:table.cell variant="strong">{{ $opp->title }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="text-xs text-slate-500">{{ $opp->pipeline?->name }}</span>
                                        <flux:badge size="sm" :color="$opp->stage?->is_won ? 'emerald' : ($opp->stage?->is_lost ? 'red' : 'indigo')">
                                            {{ $opp->stage?->name }}
                                        </flux:badge>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ number_format((float) $opp->amount, 0, ',', '.') }} ₫
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell>{{ $opp->owner?->name ?: 'Chưa phân công' }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button :href="route('opportunities.show', ['opportunityId' => $opp->id])" wire:navigate size="sm" variant="ghost">Chi tiết</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center py-6 text-slate-500">Chưa có cơ hội bán hàng nào liên kết với doanh nghiệp này.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @elseif ($activeTab === 'contacts')
        <div class="crm-card">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Danh sách Người liên hệ (Contacts)</h3>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Họ tên / Đại diện</flux:table.column>
                        <flux:table.column>Chức danh</flux:table.column>
                        <flux:table.column>Email</flux:table.column>
                        <flux:table.column>Số điện thoại</flux:table.column>
                        <flux:table.column align="end">Thao tác</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($data['contacts'] as $contact)
                            <flux:table.row :key="$contact->id">
                                <flux:table.cell variant="strong">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $contact->full_name }}</span>
                                        @if ($contact->is_primary)
                                            <flux:badge color="emerald" size="sm">Đại diện chính</flux:badge>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $contact->job_title ?: '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $contact->email ?: '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $contact->phone ?: '—' }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button :href="route('contacts.show', ['contactId' => $contact->id])" wire:navigate size="sm" variant="ghost">Chi tiết</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center py-6 text-slate-500">Chưa có người liên hệ nào được ghi nhận cho doanh nghiệp này.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @elseif ($activeTab === 'tasks')
        <div class="crm-card">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Danh sách Công việc & Nhiệm vụ</h3>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Tiêu đề Công việc</flux:table.column>
                        <flux:table.column>Trạng thái</flux:table.column>
                        <flux:table.column>Hạn hoàn thành</flux:table.column>
                        <flux:table.column>Người thực hiện</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($data['tasks'] as $task)
                            <flux:table.row :key="$task->id">
                                <flux:table.cell variant="strong">{{ $task->title }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$task->status->value === 'completed' ? 'emerald' : 'amber'">
                                        {{ $task->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{ $task->due_date ? $task->due_date->format('d/m/Y H:i') : '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $task->assignee?->name ?: 'Chưa gán' }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center py-6 text-slate-500">Chưa có công việc nào liên quan tới doanh nghiệp này.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endif
</div>
