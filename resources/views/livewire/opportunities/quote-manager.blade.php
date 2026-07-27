<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Báo giá (Quotes & Proposals)</h3>
            <p class="mt-1 text-xs text-slate-500">Quản lý các bản báo giá thương lượng dành cho khách hàng.</p>
        </div>

        <flux:button wire:click="openCreateModal" variant="primary" size="sm" icon="document-plus">Tạo báo giá</flux:button>
    </div>

    @if ($feedbackMessage)
        <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
            <span>{{ $feedbackMessage }}</span>
            <button type="button" class="text-xs font-semibold hover:underline" wire:click="$set('feedbackMessage', '')">Ẩn</button>
        </div>
    @endif

    @if ($this->quotes->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-800">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Chưa có bản báo giá nào</p>
            <p class="mt-1 text-xs text-slate-400">Tạo báo giá mới để gửi chi tiết chi phí dịch vụ cho khách hàng.</p>
            <div class="mt-4">
                <flux:button wire:click="openCreateModal" size="sm" variant="outline" icon="document-plus">Khởi tạo báo giá đầu tiên</flux:button>
            </div>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-xs">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Mã Báo giá</flux:table.column>
                    <flux:table.column>Trạng thái</flux:table.column>
                    <flux:table.column align="end">Tiền hàng (Subtotal)</flux:table.column>
                    <flux:table.column align="end">Thuế VAT</flux:table.column>
                    <flux:table.column align="end">Tổng cộng</flux:table.column>
                    <flux:table.column>Hạn hiệu lực</flux:table.column>
                    <flux:table.column>Người tạo</flux:table.column>
                    <flux:table.column align="end">Thao tác</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->quotes as $quote)
                        <flux:table.row :key="$quote->id">
                            <flux:table.cell variant="strong">
                                <span class="font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $quote->quote_number }}</span>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ count($quote->items) }} sản phẩm / dịch vụ</p>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$quote->status->color()" size="sm">{{ $quote->status->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ number_format((float) $quote->subtotal, 0, ',', '.') }} ₫</span>
                                @if ((float) $quote->discount_amount > 0)
                                    <p class="mt-0.5 text-[11px] text-emerald-600">KM -{{ number_format((float) $quote->discount_amount, 0, ',', '.') }} ₫</p>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">+{{ number_format((float) $quote->tax_amount, 0, ',', '.') }} ₫</span>
                                <p class="mt-0.5 text-[11px] text-slate-400">VAT {{ number_format((float) $quote->tax_percent, 0) }}%</p>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ number_format((float) $quote->total_amount, 0, ',', '.') }} ₫</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span>{{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : '—' }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span>{{ $quote->creator?->name ?? '—' }}</span>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $quote->created_at?->format('d/m/Y H:i') }}</p>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    <flux:button :href="route('quotes.show', $quote->id)" target="_blank" size="sm" variant="outline" icon="printer">Xem / In</flux:button>

                                    @if ($quote->status->value === 'draft')
                                        <flux:button wire:click="changeStatus({{ $quote->id }}, 'sent')" size="sm" variant="ghost">Gửi</flux:button>
                                    @elseif($quote->status->value === 'sent')
                                        <flux:button wire:click="changeStatus({{ $quote->id }}, 'accepted')" size="sm" variant="ghost">Đồng ý</flux:button>
                                    @endif

                                    <flux:button wire:click="deleteQuote({{ $quote->id }})" size="sm" variant="danger" icon="trash" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    <!-- Create Quote Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 dark:border dark:border-slate-800">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Tạo Báo giá mới</h3>
                    <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-white" wire:click="$set('showCreateModal', false)">✕</button>
                </div>

                <form wire:submit.prevent="createQuote" class="mt-4 space-y-4">
                    <flux:input wire:model="validUntil" type="date" label="Thời hạn hiệu lực báo giá" required />

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="taxPercent" type="number" step="0.5" min="0" max="100" label="Thuế VAT (%)" />
                        <div>
                            <flux:input wire:model="discountAmount" type="number" step="100000" min="0" label="Khuyến mãi (VNĐ)" placeholder="Ví dụ: 5000000" />
                            <p class="mt-1 text-[11px] text-slate-500">Giảm trừ khuyến mãi (VNĐ). Tối đa bằng tiền hàng.</p>
                        </div>
                    </div>

                    <flux:textarea wire:model="notes" label="Ghi chú điều khoản / thanh toán" placeholder="Nhập ghi chú hoặc điều khoản thanh toán..." rows="3" />

                    <div class="mt-6 flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <flux:button type="button" variant="ghost" wire:click="$set('showCreateModal', false)">Hủy</flux:button>
                        <flux:button type="submit" variant="primary">Khởi tạo Báo giá</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
