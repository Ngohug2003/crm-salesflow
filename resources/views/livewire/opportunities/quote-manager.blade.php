<div class="space-y-5">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Báo giá</h3>
            <p class="mt-1 text-sm text-slate-500">Tạo, phê duyệt và phát hành tài liệu báo giá theo phiên bản.</p>
        </div>
        @can('create', \App\Models\Quote::class)
            <flux:button wire:click="openCreateModal" variant="primary" size="sm">Tạo báo giá</flux:button>
        @endcan
    </div>

    @if ($feedbackMessage)
        <div class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            <span>{{ $feedbackMessage }}</span>
            <button type="button" class="font-medium" wire:click="$set('feedbackMessage', '')">Đóng</button>
        </div>
    @endif
    @if ($errorMessage && ! $showSubmitModal)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $errorMessage }}</div>
    @endif

    @if ($this->quotes->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 px-5 py-10 text-center dark:border-slate-700">
            <p class="font-medium text-slate-700 dark:text-slate-200">Chưa có báo giá</p>
            <p class="mt-1 text-sm text-slate-500">Dòng sản phẩm và khách hàng từ cơ hội sẽ được đưa vào báo giá tự động.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Báo giá</flux:table.column>
                    <flux:table.column>Trạng thái</flux:table.column>
                    <flux:table.column align="end">Chiết khấu</flux:table.column>
                    <flux:table.column align="end">Tổng thanh toán</flux:table.column>
                    <flux:table.column>Hiệu lực</flux:table.column>
                    <flux:table.column align="end">Thao tác</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->quotes as $quote)
                        @php
                            $approval = $quote->approvalRequests->first();
                            $document = $quote->documents->first();
                        @endphp
                        <flux:table.row wire:key="quote-{{ $quote->id }}">
                            <flux:table.cell>
                                <p class="font-mono text-sm font-semibold text-slate-900 dark:text-white">{{ $quote->quote_number }}</p>
                                <p class="mt-1 text-xs text-slate-500">Phiên bản {{ $quote->version }} · {{ $quote->items->count() }} dòng</p>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$quote->status->color()" size="sm">{{ $quote->status->label() }}</flux:badge>
                                @if ($quote->status === \App\Enums\QuoteStatus::Rejected && $quote->rejection_reason)
                                    <p class="mt-1 max-w-52 text-xs text-red-600">{{ $quote->rejection_reason }}</p>
                                @elseif ($approval?->required_role && $quote->status === \App\Enums\QuoteStatus::PendingApproval)
                                    <p class="mt-1 text-xs text-slate-500">Cấp duyệt: {{ $approval->required_role }}</p>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <span class="font-medium">{{ $quote->discount_percent }}%</span>
                                <p class="text-xs text-slate-500">-{{ number_format((float) $quote->discount_amount, 0, ',', '.') }} ₫</p>
                            </flux:table.cell>
                            <flux:table.cell align="end"><span class="font-semibold">{{ number_format((float) $quote->total_amount, 0, ',', '.') }} ₫</span></flux:table.cell>
                            <flux:table.cell>{{ $quote->valid_until?->format('d/m/Y') ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <flux:button :href="route('quotes.show', $quote->id)" target="_blank" size="sm" variant="ghost">Xem</flux:button>
                                    @if (! $this->administrator)
                                        @can('submit', $quote)
                                            <flux:button wire:click="openSubmitModal({{ $quote->id }})" size="sm" variant="outline">Gửi duyệt</flux:button>
                                        @endcan
                                    @endif
                                    @if (
                                        $approval?->status === \App\Enums\QuoteApprovalStatus::Pending
                                        && $approval->requested_by !== auth()->id()
                                    )
                                        @can('approve', $quote)
                                            <flux:button wire:click="openApprovalDecision({{ $approval->id }}, 'reject')" size="sm" variant="outline">Từ chối</flux:button>
                                            <flux:button wire:click="openApprovalDecision({{ $approval->id }}, 'approve')" size="sm" variant="primary">Phê duyệt</flux:button>
                                        @endcan
                                    @endif
                                    @can('issue', $quote)
                                        <flux:button wire:click="issue({{ $quote->id }})" wire:loading.attr="disabled" wire:target="issue({{ $quote->id }})" size="sm" variant="primary">Phát hành PDF</flux:button>
                                    @endcan
                                    @if ($document?->status === 'ready' && $quote->status->canDownloadDocument())
                                        @can('download', $quote)
                                            <flux:button :href="$this->documentUrl($quote->id, $document->id)" size="sm" variant="outline">Tải PDF</flux:button>
                                        @endcan
                                    @elseif ($document?->status === 'processing')
                                        <span class="self-center text-xs text-slate-500">Đang tạo PDF…</span>
                                    @elseif ($document?->status === 'failed')
                                        <span class="self-center text-xs text-red-600">Tạo PDF lỗi</span>
                                    @endif
                                    @if (! $this->administrator)
                                        @can('send', $quote)
                                            <flux:button wire:click="markSent({{ $quote->id }})" size="sm" variant="ghost">Đánh dấu đã gửi</flux:button>
                                        @endcan
                                    @endif
                                    @can('delete', $quote)
                                        <flux:button wire:click="deleteQuote({{ $quote->id }})" wire:confirm="Xóa báo giá nháp này?" size="sm" variant="ghost">Xóa</flux:button>
                                    @endcan
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    <flux:modal name="create-quote" wire:model="showCreateModal" class="md:w-[36rem]">
        <form wire:submit="createQuote" class="space-y-5">
            <div>
                <flux:heading size="lg">Tạo báo giá</flux:heading>
                <flux:text class="mt-2">Khách hàng và các dòng sản phẩm được lấy từ cơ hội hiện tại.</flux:text>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="validUntil" type="date" label="Hiệu lực đến" required />
                <flux:input wire:model="taxPercent" type="number" step="0.01" min="0" max="100" label="Thuế VAT (%)" required />
            </div>
            <flux:input wire:model="discountAmount" type="number" step="1000" min="0" label="Chiết khấu toàn báo giá (VNĐ)" required />
            <flux:textarea wire:model="notes" label="Điều khoản / Ghi chú" rows="4" />
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showCreateModal', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="createQuote">
                    <span wire:loading.remove wire:target="createQuote">Tạo báo giá</span>
                    <span wire:loading wire:target="createQuote">Đang tạo…</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="submit-quote" wire:model="showSubmitModal" class="md:w-[32rem]">
        <form wire:submit="submitForApproval" class="space-y-5">
            <div>
                <flux:heading size="lg">Gửi phê duyệt báo giá</flux:heading>
                <flux:text class="mt-2">Hệ thống tự xác định cấp duyệt theo phần trăm chiết khấu.</flux:text>
            </div>
            @if ($errorMessage)
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $errorMessage }}</div>
            @endif
            <flux:textarea wire:model="approvalNote" label="Ghi chú cho người phê duyệt" rows="4" />
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showSubmitModal', false)">Hủy</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="submitForApproval">Gửi phê duyệt</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="quote-inline-decision" wire:model="showDecisionModal" class="md:w-[32rem]">
        <form wire:submit="resolveApprovalDecision" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $decision === 'approve' ? 'Phê duyệt báo giá' : 'Từ chối báo giá' }}</flux:heading>
                <flux:text class="mt-2">
                    {{ $decision === 'approve'
                        ? 'Xác nhận báo giá đủ điều kiện để Sales phát hành.'
                        : 'Lý do sẽ được gửi cho người lập để điều chỉnh báo giá.' }}
                </flux:text>
            </div>
            @if ($errorMessage)
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $errorMessage }}</div>
            @endif
            <flux:textarea
                wire:model="decisionReason"
                :label="$decision === 'approve' ? 'Ghi chú phê duyệt (không bắt buộc)' : 'Lý do từ chối'"
                rows="4"
            />
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showDecisionModal', false)">Hủy</flux:button>
                <flux:button type="submit" :variant="$decision === 'approve' ? 'primary' : 'danger'" wire:loading.attr="disabled" wire:target="resolveApprovalDecision">
                    <span wire:loading.remove wire:target="resolveApprovalDecision">Xác nhận</span>
                    <span wire:loading wire:target="resolveApprovalDecision">Đang xử lý…</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
