<div>
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">CRM / Báo giá</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">Báo giá chờ phê duyệt</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Kiểm tra mức chiết khấu, giá trị báo giá và phản hồi cho người lập.</p>
        </div>
        @can('manageSettings', \App\Models\Quote::class)
            <flux:button :href="route('quotes.settings')" wire:navigate variant="outline">Cấu hình báo giá</flux:button>
        @endcan
    </div>

    @if (session()->has('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-5 flex gap-2 overflow-x-auto border-b border-slate-200 pb-3 dark:border-slate-800">
        @foreach (['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'] as $value => $label)
            <flux:button wire:click="$set('status', '{{ $value }}')" size="sm" :variant="$status === $value ? 'primary' : 'ghost'">{{ $label }}</flux:button>
        @endforeach
    </div>

    <section class="crm-card overflow-hidden">
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Báo giá</flux:table.column>
                    <flux:table.column>Khách hàng</flux:table.column>
                    <flux:table.column>Người đề nghị</flux:table.column>
                    <flux:table.column align="end">Chiết khấu</flux:table.column>
                    <flux:table.column align="end">Tổng thanh toán</flux:table.column>
                    <flux:table.column>Quy tắc</flux:table.column>
                    <flux:table.column align="end">Thao tác</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($requests as $request)
                        <flux:table.row wire:key="approval-{{ $request->id }}">
                            <flux:table.cell>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $request->quote->quote_number }}</p>
                                <p class="text-xs text-slate-500">Phiên bản {{ $request->quote_version }} · {{ $request->submitted_at?->format('d/m/Y H:i') }}</p>
                            </flux:table.cell>
                            <flux:table.cell>
                                <p class="text-sm text-slate-800 dark:text-slate-200">{{ $request->quote->opportunity?->company?->name ?? '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $request->quote->opportunity?->title }}</p>
                            </flux:table.cell>
                            <flux:table.cell>{{ $request->requester?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end"><span class="font-semibold text-amber-700 dark:text-amber-300">{{ $request->discount_percent }}%</span></flux:table.cell>
                            <flux:table.cell align="end"><span class="font-semibold">{{ number_format((float) $request->total_amount, 0, ',', '.') }} ₫</span></flux:table.cell>
                            <flux:table.cell>
                                <p class="text-sm">{{ $request->rule?->name ?? 'Quy tắc đã ngừng' }}</p>
                                <p class="text-xs text-slate-500">{{ $request->required_role ? 'Cần vai trò: '.$request->required_role : 'Tự động' }}</p>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($request->status === \App\Enums\QuoteApprovalStatus::Pending)
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" variant="outline" wire:click="openDecision({{ $request->id }}, 'reject')">Từ chối</flux:button>
                                        <flux:button size="sm" variant="primary" wire:click="openDecision({{ $request->id }}, 'approve')">Phê duyệt</flux:button>
                                    </div>
                                @else
                                    <flux:badge :color="$request->status === \App\Enums\QuoteApprovalStatus::Approved ? 'emerald' : 'red'">{{ $request->status->label() }}</flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <div class="py-10 text-center">
                                    <p class="font-medium text-slate-700 dark:text-slate-200">Không có báo giá trong nhóm này</p>
                                    <p class="mt-1 text-sm text-slate-500">Các yêu cầu phù hợp phạm vi phòng ban sẽ xuất hiện tại đây.</p>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $requests->links() }}</div>
    </section>

    <flux:modal name="quote-decision" wire:model="showDecisionModal" class="md:w-[32rem]">
        <form wire:submit="resolve" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $decision === 'approve' ? 'Phê duyệt báo giá' : 'Từ chối báo giá' }}</flux:heading>
                <flux:text class="mt-2">{{ $decision === 'approve' ? 'Xác nhận báo giá đủ điều kiện để phát hành.' : 'Lý do sẽ được gửi cho người lập để điều chỉnh.' }}</flux:text>
            </div>
            @if ($errorMessage)
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $errorMessage }}</div>
            @endif
            <flux:textarea wire:model="reason" :label="$decision === 'approve' ? 'Ghi chú phê duyệt (không bắt buộc)' : 'Lý do từ chối'" rows="4" />
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showDecisionModal', false)">Hủy</flux:button>
                <flux:button type="submit" :variant="$decision === 'approve' ? 'primary' : 'danger'" wire:loading.attr="disabled" wire:target="resolve">
                    <span wire:loading.remove wire:target="resolve">Xác nhận</span>
                    <span wire:loading wire:target="resolve">Đang xử lý…</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
