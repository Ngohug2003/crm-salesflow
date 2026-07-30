<div class="space-y-6">
    {{-- Flash Message --}}
    @if (session()->has('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Main Public Quote Document Card --}}
    <div class="crm-card p-6 md:p-8 space-y-8">
        {{-- Document Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-slate-200 pb-6 dark:border-slate-800">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-sm font-semibold text-indigo-600 dark:text-indigo-400">#{{ $quote->quote_number }}</span>
                    <flux:badge color="zinc" size="sm">Phiên bản {{ $link->version_issued }}</flux:badge>
                </div>
                <h1 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">BÁO GIÁ DỊCH VỤ / SẢN PHẨM</h1>
                <p class="text-xs text-slate-500 mt-1">Ngày phát hành: {{ $quote->issued_at ? $quote->issued_at->format('d/m/Y') : ($quote->sent_at ? $quote->sent_at->format('d/m/Y') : now()->format('d/m/Y')) }}</p>
            </div>

            <div class="flex flex-col items-start sm:items-end gap-2">
                <div class="text-left sm:text-right">
                    <div class="text-xs text-slate-500">Hiệu lực đến ngày:</div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-white">
                        {{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : 'Chưa giới hạn' }}
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    @if ($quote->status->value === 'accepted')
                        <flux:badge color="emerald" size="sm" class="font-bold uppercase">Đã chấp thuận</flux:badge>
                    @elseif ($quote->status->value === 'declined')
                        <flux:badge color="rose" size="sm" class="font-bold uppercase">Bị từ chối</flux:badge>
                    @else
                        <flux:badge color="indigo" size="sm" class="font-bold uppercase">Đang hiệu lực</flux:badge>
                    @endif

                    <a href="{{ route('quotes.public-pdf', ['token' => $token]) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 text-xs font-semibold shadow-sm transition">
                        <flux:icon.arrow-down-tray class="size-3.5" />
                        <span>Tải PDF</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Parties Information --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-900/50">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Đơn vị cung cấp</p>
                <h3 class="mt-1 font-bold text-slate-900 dark:text-white">Công ty Cổ phần SalesFlow CRM</h3>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">Email: sales@salesflow.vn | Hotline: 1900 6868</p>
                <p class="text-xs text-slate-600 dark:text-slate-400">Địa chỉ: Tầng 8, Tòa nhà Công nghệ, Hà Nội</p>
            </div>

            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-900/50">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Khách hàng nhận báo giá</p>
                <h3 class="mt-1 font-bold text-slate-900 dark:text-white">{{ $quote->company?->name ?: 'Khách hàng cá nhân' }}</h3>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                    Người liên hệ: <span class="font-medium text-slate-800 dark:text-slate-200">{{ $quote->contact?->name ?: 'Đại diện Doanh nghiệp' }}</span>
                </p>
                <p class="text-xs text-slate-600 dark:text-slate-400">Email: {{ $quote->contact?->email ?: 'N/A' }} | Điện thoại: {{ $quote->contact?->phone ?: 'N/A' }}</p>
            </div>
        </div>

        {{-- Quote Items Table --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Chi tiết hạng mục dịch vụ / sản phẩm</h3>
            <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3">#</th>
                            <th class="p-3">Tên sản phẩm / Dịch vụ</th>
                            <th class="p-3 text-center">Số lượng</th>
                            <th class="p-3 text-right">Đơn giá (₫)</th>
                            <th class="p-3 text-right">Thành tiền (₫)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($quote->items as $index => $item)
                            <tr>
                                <td class="p-3 text-slate-500">{{ $index + 1 }}</td>
                                <td class="p-3">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ $item->product_name }}</div>
                                    @if ($item->description)
                                        <div class="text-[11px] text-slate-500">{{ $item->description }}</div>
                                    @endif
                                </td>
                                <td class="p-3 text-center text-slate-800 dark:text-slate-200">{{ number_format($item->quantity) }}</td>
                                <td class="p-3 text-right text-slate-800 dark:text-slate-200">{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                <td class="p-3 text-right font-medium text-slate-900 dark:text-white">{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-slate-500">Chưa có thông tin hạng mục chi tiết.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Total Calculation & Terms --}}
        <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-800">
            <div class="flex flex-col gap-6 sm:flex-row sm:justify-between items-start">
                {{-- Terms & Notes --}}
                <div class="max-w-md text-xs text-slate-600 dark:text-slate-400 space-y-2">
                    <p class="font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">ĐIỀU KHOẢN & GHI CHÚ THANH TOÁN</p>
                    <div class="space-y-1 leading-relaxed">
                        <p>• Báo giá có giá trị hiệu lực đến ngày {{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : 'hết hạn' }}.</p>
                        <p>• Phương thức thanh toán: Chuyển khoản ngân hàng.</p>
                        <p>• Giá chưa bao gồm các chi phí phát sinh bổ sung ngoài danh mục.</p>
                        @if ($quote->notes)
                            <p class="mt-2 text-slate-500 italic">{{ $quote->notes }}</p>
                        @endif
                    </div>
                </div>

                {{-- Totals Breakdown --}}
                <div class="w-full sm:w-80 space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800 text-slate-600 dark:text-slate-400">
                        <span>Tổng tiền hàng:</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ number_format((float) $quote->subtotal, 0, ',', '.') }} đ</span>
                    </div>

                    @if ((float) $quote->discount_amount > 0)
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800 text-emerald-600 dark:text-emerald-400">
                            <span>Khuyến mãi tổng đơn:</span>
                            <span class="font-medium">-{{ number_format((float) $quote->discount_amount, 0, ',', '.') }} đ</span>
                        </div>
                    @endif

                    <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400">
                        <span>Thuế GTGT (VAT {{ number_format((float) $quote->tax_percent, 0) }}%):</span>
                        <span class="font-medium text-slate-900 dark:text-white">+{{ number_format((float) $quote->tax_amount, 0, ',', '.') }} đ</span>
                    </div>

                    <div class="flex justify-between py-2 border-t-2 border-slate-900 dark:border-white text-sm font-bold">
                        <span class="text-slate-900 dark:text-white uppercase">TỔNG CỘNG THANH TOÁN:</span>
                        <span class="text-indigo-600 dark:text-indigo-400 text-base">{{ number_format((float) $quote->total_amount, 0, ',', '.') }} đ</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Signatures Section --}}
        <div class="mt-12 grid grid-cols-2 gap-8 text-center text-xs border-t border-slate-100 pt-8 dark:border-slate-800">
            <div>
                <p class="font-bold text-slate-900 dark:text-white uppercase tracking-wider">ĐẠI DIỆN KHÁCH HÀNG</p>
                <p class="text-[11px] text-slate-400 italic">(Ký, ghi rõ họ tên và đóng dấu)</p>
                <div class="h-20 flex items-center justify-center">
                    @if ($isSubmitted && $signerName)
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400 text-xs bg-emerald-50 dark:bg-emerald-950/50 px-3 py-1 rounded border border-emerald-200 dark:border-emerald-800">
                            Đã xác nhận: {{ $signerName }}
                        </span>
                    @endif
                </div>
                <p class="font-semibold text-slate-800 dark:text-slate-200">
                    {{ $signerName ?: ($quote->contact?->name ?: 'Đại diện Khách hàng') }}
                </p>
            </div>

            <div>
                <p class="font-bold text-slate-900 dark:text-white uppercase tracking-wider">ĐẠI DIỆN SALESFLOW CRM</p>
                <p class="text-[11px] text-slate-400 italic">(Ký, ghi rõ họ tên và đóng dấu)</p>
                <div class="h-20"></div>
                <p class="font-semibold text-slate-800 dark:text-slate-200">
                    {{ $quote->creator?->name ?: 'SalesFlow Admin' }}
                </p>
            </div>
        </div>

        {{-- Customer Acceptance Action Bar --}}
        <div class="border-t border-slate-200 pt-6 dark:border-slate-800">
            @if ($isSubmitted)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                    <div class="flex items-center gap-2">
                        @if ($submittedResponseType === 'accept')
                            <flux:badge color="emerald" size="sm" class="font-bold">ĐÃ CHẤP THUẬN</flux:badge>
                        @elseif ($submittedResponseType === 'decline')
                            <flux:badge color="rose" size="sm" class="font-bold">ĐÃ TỪ CHỐI</flux:badge>
                        @else
                            <flux:badge color="indigo" size="sm" class="font-bold">ĐÃ GỬI PHẢN HỒI</flux:badge>
                        @endif
                        <span class="text-xs text-slate-500">Phản hồi đã được ghi nhận thành công.</span>
                    </div>

                    <div class="mt-3 text-xs text-slate-700 dark:text-slate-300 space-y-1">
                        <div>Người thực hiện: <span class="font-semibold">{{ $signerName }}</span> ({{ $signerEmail }})</div>
                        @if ($signerTitle)
                            <div>Chức danh: {{ $signerTitle }}</div>
                        @endif
                        @if ($feedbackNotes)
                            <div class="mt-1 italic text-slate-600 dark:text-slate-400">"{{ $feedbackNotes }}"</div>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('quotes.public-pdf', ['token' => $token]) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-800 dark:bg-slate-200 text-white dark:text-slate-900 px-4 py-2 text-xs font-semibold hover:bg-slate-700 dark:hover:bg-white transition">
                        <flux:icon.arrow-down-tray class="size-4" />
                        <span>Tải PDF chính thức</span>
                    </a>
                    <flux:button variant="subtle" icon="chat-bubble-left-right" wire:click="openResponseModal('comment')">
                        Gửi câu hỏi / Ý kiến
                    </flux:button>
                    <flux:button variant="ghost" icon="x-mark" wire:click="openResponseModal('decline')">
                        Từ chối báo giá
                    </flux:button>
                    <flux:button variant="primary" icon="check" wire:click="openResponseModal('accept')">
                        Chấp thuận Báo giá
                    </flux:button>
                </div>
            @endif
        </div>
    </div>

    {{-- Response Action Modal --}}
    <flux:modal name="public-response-modal" wire:model="showResponseModal" class="w-full max-w-lg">
        <form wire:submit.prevent="submitResponse" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    @if ($activeAction === 'accept')
                        Xác nhận Chấp thuận Báo giá #{{ $quote->quote_number }}
                    @elseif ($activeAction === 'decline')
                        Xác nhận Từ chối Báo giá #{{ $quote->quote_number }}
                    @else
                        Gửi câu hỏi / Phản hồi Báo giá #{{ $quote->quote_number }}
                    @endif
                </flux:heading>
                <flux:subheading class="mt-1">
                    Vui lòng điền thông tin người đại diện để hoàn tất gửi phản hồi đến đơn vị cung cấp.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Họ và tên người đại diện *</flux:label>
                    <flux:input wire:model="signerName" placeholder="Ví dụ: Nguyễn Văn A" />
                    <flux:error name="signerName" />
                </flux:field>

                <flux:field>
                    <flux:label>Email người đại diện *</flux:label>
                    <flux:input wire:model="signerEmail" type="email" placeholder="nguyenvana@company.com" />
                    <flux:error name="signerEmail" />
                </flux:field>

                <flux:field>
                    <flux:label>Chức danh / Vai trò</flux:label>
                    <flux:input wire:model="signerTitle" placeholder="Ví dụ: Giám đốc Mua hàng / Trưởng phòng IT" />
                    <flux:error name="signerTitle" />
                </flux:field>

                <flux:field>
                    <flux:label>Ghi chú / Ý kiến phản hồi {{ $activeAction === 'decline' ? '*' : '' }}</flux:label>
                    <flux:textarea wire:model="feedbackNotes" placeholder="Ví dụ: Chúng tôi đồng ý với báo giá này / Vui lòng điều chỉnh điều khoản thanh toán..." rows="3" />
                    <flux:error name="feedbackNotes" />
                </flux:field>
            </div>

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showResponseModal', false)">Hủy</flux:button>
                <flux:button type="submit" variant="{{ $activeAction === 'decline' ? 'danger' : 'primary' }}">
                    Gửi xác nhận
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
