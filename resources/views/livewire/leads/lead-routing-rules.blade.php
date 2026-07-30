<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Khách hàng tiềm năng</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">Phân bổ Lead và SLA</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Thiết lập thứ tự, điều kiện phân bổ và theo dõi thời hạn phản hồi Lead.</p>
        </div>
        <div class="flex items-center gap-3">
            <flux:button variant="primary" icon="plus" wire:click="openCreateRule">Tạo quy tắc phân bổ</flux:button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('warning'))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200" role="status">
            {{ session('warning') }}
        </div>
    @endif

    <div class="mb-6 border-b border-slate-200 dark:border-slate-800">
        <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Nội dung phân bổ Lead">
            <button wire:click="$set('activeTab', 'rules')" @class(['pb-3 text-sm font-medium border-b-2 transition-colors', 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' => $activeTab === 'rules', 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400' => $activeTab !== 'rules'])>
                Quy tắc <span class="text-xs">({{ $rules->count() }})</span>
            </button>
            <button wire:click="$set('activeTab', 'unassigned')" @class(['pb-3 text-sm font-medium border-b-2 transition-colors', 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' => $activeTab === 'unassigned', 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400' => $activeTab !== 'unassigned'])>
                Lead chờ xử lý <span class="text-xs">({{ $unassignedLeads->total() }})</span>
            </button>
            <button wire:click="$set('activeTab', 'executions')" @class(['pb-3 text-sm font-medium border-b-2 transition-colors', 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' => $activeTab === 'executions', 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400' => $activeTab !== 'executions'])>
                Lịch sử phân bổ <span class="text-xs">({{ $executions->total() }})</span>
            </button>
        </nav>
    </div>

    @if ($activeTab === 'rules')
        <section class="crm-card">
            <div class="overflow-x-auto">
                <flux:table class="w-full text-left text-sm">
                    <flux:table.columns>
                        <flux:table.column class="w-16">Ưu tiên</flux:table.column>
                        <flux:table.column>Tên quy tắc</flux:table.column>
                        <flux:table.column>Chiến lược</flux:table.column>
                        <flux:table.column>Điều kiện khớp</flux:table.column>
                        <flux:table.column>Lượt phân công gần nhất</flux:table.column>
                        <flux:table.column>Trạng thái</flux:table.column>
                        <flux:table.column align="end" class="w-28">Thao tác</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($rules as $rule)
                            <flux:table.row>
                                <flux:table.cell class="font-mono text-xs font-medium text-slate-600 dark:text-slate-300">
                                    #{{ $rule->priority }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $rule->name }}</p>
                                    <p class="text-xs text-slate-500">Phòng ban: {{ $rule->department?->name ?: 'Tất cả phòng ban kinh doanh' }}</p>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-sm text-slate-700 dark:text-slate-300">
                                        {{ match ($rule->strategy) {
                                            'round_robin' => 'Xoay vòng',
                                            'territory' => 'Theo địa bàn',
                                            'value_threshold' => 'Theo giá trị',
                                            default => $rule->strategy,
                                        } }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="text-xs text-slate-600 dark:text-slate-400">
                                    @forelse ($rule->conditions as $cond)
                                        <div class="space-y-0.5">
                                            @if ($cond->leadSource)
                                                <p>Nguồn: {{ $cond->leadSource->name }}</p>
                                            @endif
                                            @if ($cond->min_estimated_value)
                                                <p>Giá trị từ: {{ number_format((float) $cond->min_estimated_value, 0, ',', '.') }} ₫</p>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-slate-400">Không giới hạn điều kiện</span>
                                    @endforelse
                                </flux:table.cell>
                                <flux:table.cell class="text-xs text-slate-600 dark:text-slate-400">
                                    {{ $rule->cursor?->lastAssignedUser?->name ?: 'Chưa phân công lượt nào' }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <button type="button" wire:click="toggleRuleStatus({{ $rule->id }})">
                                        <flux:badge color="{{ $rule->is_active ? 'emerald' : 'slate' }}" size="sm">
                                            {{ $rule->is_active ? 'Đang bật' : 'Đang tắt' }}
                                        </flux:badge>
                                    </button>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button size="xs" variant="ghost" icon="pencil" wire:click="editRule({{ $rule->id }})">Sửa</flux:button>
                                        <flux:button size="xs" variant="subtle" color="red" icon="trash" wire:click="deleteRule({{ $rule->id }})">Xóa</flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="py-8 text-center text-slate-500">
                                    Chưa có quy tắc phân bổ tự động nào. Nhấn "Tạo quy tắc phân bổ" để bắt đầu.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </section>
    @elseif ($activeTab === 'unassigned')
        <section class="crm-card">
            <div class="overflow-x-auto">
                <flux:table class="w-full text-left text-sm">
                    <flux:table.columns>
                        <flux:table.column>Lead</flux:table.column>
                        <flux:table.column>Nguồn / Địa bàn</flux:table.column>
                        <flux:table.column>Trạng thái SLA</flux:table.column>
                        <flux:table.column>Ngày tạo</flux:table.column>
                        <flux:table.column align="end" class="w-36">Thao tác</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($unassignedLeads as $lead)
                            <flux:table.row>
                                <flux:table.cell>
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $lead->full_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $lead->email ?: $lead->phone }}</p>
                                </flux:table.cell>
                                <flux:table.cell class="text-xs text-slate-600 dark:text-slate-400">
                                    <div>Nguồn: {{ $lead->source?->name ?: 'Chưa xác định' }}</div>
                                    <div>Địa bàn: {{ $lead->provinceUnit?->name ?: ($lead->province ?: 'Chưa xác định') }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($lead->is_sla_overdue)
                                        <flux:badge color="rose" size="sm">Quá hạn SLA</flux:badge>
                                    @elseif ($lead->owner_id === null)
                                        <flux:badge color="amber" size="sm">Chưa phân công</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-xs text-slate-500">
                                    {{ $lead->created_at->format('H:i d/m/Y') }}
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button size="xs" variant="primary" icon="bolt" wire:click="routeSingleLead({{ $lead->id }})">Phân bổ tự động</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="py-8 text-center text-slate-500">
                                    Không có Lead đang chờ phân bổ hoặc quá hạn SLA.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="data-list-pagination">
                {{ $unassignedLeads->links() }}
            </div>
        </section>
    @elseif ($activeTab === 'executions')
        <section class="crm-card">
            <div class="overflow-x-auto">
                <flux:table class="w-full text-left text-sm">
                    <flux:table.columns>
                        <flux:table.column class="w-32 whitespace-nowrap">Thời gian</flux:table.column>
                        <flux:table.column class="min-w-[12rem]">Lead</flux:table.column>
                        <flux:table.column class="min-w-[12rem]">Người phụ trách</flux:table.column>
                        <flux:table.column class="min-w-[16rem]">Quy tắc và điều kiện</flux:table.column>
                        <flux:table.column class="w-32 whitespace-nowrap">Hạn SLA</flux:table.column>
                        <flux:table.column class="w-28 whitespace-nowrap">Trạng thái</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($executions as $exec)
                            <flux:table.row class="align-top">
                                <flux:table.cell class="w-32 whitespace-nowrap text-xs text-slate-500">
                                    {{ $exec->created_at->format('H:i:s d/m/Y') }}
                                </flux:table.cell>
                                <flux:table.cell class="min-w-[12rem]">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $exec->lead?->full_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $exec->lead?->phone ?: $exec->lead?->email }}</p>
                                    @if ($exec->lead?->provinceUnit)
                                        <p class="mt-1 text-xs text-slate-500">{{ $exec->lead->provinceUnit->name }}</p>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="min-w-[12rem]">
                                    @if ($exec->assignedUser)
                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $exec->assignedUser->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $exec->assignedUser->email }}</p>
                                        @if ($exec->assignedUser->staffProfile?->workProvince)
                                            <p class="mt-1 text-xs text-slate-500">Địa bàn: {{ $exec->assignedUser->staffProfile->workProvince->name }}</p>
                                        @endif
                                    @else
                                        <span class="text-slate-400">Chưa có người phụ trách</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="min-w-[16rem]">
                                    <div class="space-y-1">
                                        <p class="text-xs font-medium text-slate-800 dark:text-slate-200">
                                            {{ $exec->rule?->name ?: 'Quy tắc xoay vòng mặc định' }}
                                        </p>
                                        @if ($exec->rule && $exec->rule->conditions->isNotEmpty())
                                            <div class="mt-1 space-y-0.5 text-xs text-slate-500">
                                                @foreach ($exec->rule->conditions as $cond)
                                                    @if ($cond->province)
                                                        <p>Địa bàn: {{ $cond->province->name }}</p>
                                                    @endif
                                                    @if ($cond->leadSource)
                                                        <p>Nguồn: {{ $cond->leadSource->name }}</p>
                                                    @endif
                                                    @if ($cond->min_estimated_value)
                                                        <p>Giá trị từ: {{ number_format((float) $cond->min_estimated_value, 0, ',', '.') }} ₫</p>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $exec->reason }}
                                        </p>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="w-32 whitespace-nowrap text-xs text-slate-500">
                                    {{ $exec->sla_due_at ? $exec->sla_due_at->format('H:i d/m/Y') : 'Chưa đặt' }}
                                </flux:table.cell>
                                <flux:table.cell class="w-28 whitespace-nowrap">
                                    <flux:badge color="{{ $exec->status === 'success' ? 'emerald' : ($exec->status === 'unassigned_pool' ? 'amber' : 'rose') }}" size="sm">
                                        {{ match($exec->status) {
                                            'success' => 'Thành công',
                                            'unassigned_pool' => 'Chờ phân bổ',
                                            'no_candidate' => 'Không có ứng viên',
                                            default => $exec->status,
                                        } }}
                                    </flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="py-8 text-center text-slate-500">
                                    Chưa có nhật ký phân bổ nào được ghi nhận.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="data-list-pagination">
                {{ $executions->links() }}
            </div>
        </section>
    @endif

    <flux:modal name="rule-modal" wire:model="showRuleModal" class="w-full" style="width: 36rem; max-width: 95vw;">
        <form wire:submit.prevent="saveRule" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingRuleId ? 'Chỉnh sửa quy tắc phân bổ' : 'Tạo quy tắc phân bổ' }}</flux:heading>
                <flux:subheading class="mt-1">Thiết lập mức ưu tiên và điều kiện chọn người phụ trách.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Tên quy tắc</flux:label>
                    <flux:input wire:model="ruleName" placeholder="Ví dụ: Phân bổ Lead Khối Miền Bắc" />
                    <flux:error name="ruleName" />
                </flux:field>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>Thứ tự ưu tiên</flux:label>
                        <flux:input type="number" min="1" wire:model="priority" />
                        <flux:error name="priority" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Chiến lược</flux:label>
                        <flux:select wire:model="strategy">
                            <option value="round_robin">Xoay vòng (Round-Robin)</option>
                            <option value="value_threshold">Theo giá trị Lead</option>
                        </flux:select>
                        <flux:error name="strategy" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Phòng ban mục tiêu</flux:label>
                    <x-forms.modal-searchable-select
                        wire:model="departmentId"
                        :options="$departments"
                        option-value="id"
                        option-label="name"
                        placeholder="Tất cả phòng ban kinh doanh"
                        search-placeholder="Tìm phòng ban…"
                    />
                    <flux:error name="departmentId" />
                </flux:field>

                <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
                    <h3 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">Điều kiện áp dụng</h3>
                    <div class="space-y-3">
                        <flux:field>
                            <flux:label>Nguồn Lead</flux:label>
                            <x-forms.modal-searchable-select
                                wire:model="leadSourceId"
                                :options="$leadSources"
                                option-value="id"
                                option-label="name"
                                placeholder="Tất cả nguồn Lead"
                                search-placeholder="Tìm nguồn Lead…"
                            />
                            <flux:error name="leadSourceId" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Giá trị Lead tối thiểu</flux:label>
                            <x-forms.money-input model="minEstimatedValue" label="Giá trị Lead tối thiểu" />
                            <flux:error name="minEstimatedValue" />
                        </flux:field>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                <flux:button variant="ghost" wire:click="$set('showRuleModal', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveRule">Lưu quy tắc</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
