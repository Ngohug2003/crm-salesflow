<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Báo giá #{{ $quote->quote_number }} - SalesFlow CRM</title>
    @vite(['resources/css/app.css', 'resources/js/app.app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; padding: 0 !important; }
            .print-container { shadow: none !important; border: none !important; max-width: 100% !important; }
            @if (! $downloadUrl)
                body > * { display: none !important; }
                body::before { display: block; content: "Bản xem trước chưa được phê duyệt và phát hành."; font: 18px sans-serif; padding: 40px; }
            @endif
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased p-4 md:p-8">
    <!-- Top Action Bar (hidden on print) -->
    <div class="no-print mx-auto mb-6 flex max-w-4xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500">SalesFlow CRM / Báo giá / #{{ $quote->quote_number }}</span>
            <flux:badge :color="$quote->status->color()" size="sm">{{ $quote->status->label() }}</flux:badge>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
            @can('issue', $quote)
                @if ($quote->issued_snapshot && in_array($quote->status->value, ['issued', 'sent'], true))
                    <form action="{{ route('quotes.public-link.store', $quote->id) }}" method="POST" class="flex items-center gap-1.5">
                        @csrf
                        <label class="sr-only" for="access_code">Mã bảo vệ link (không bắt buộc)</label>
                        <input id="access_code" name="access_code" type="password" minlength="6" maxlength="100" placeholder="Mã bảo vệ (tuỳ chọn)" class="h-8 w-36 rounded-md border border-slate-300 bg-white px-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none" />
                        <button
                            type="submit"
                            class="inline-flex h-8 items-center gap-1.5 rounded-md bg-emerald-600 px-3 text-xs font-semibold text-white hover:bg-emerald-500"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                            <span>Tạo link công khai</span>
                        </button>
                    </form>
                @endif
            @endcan

            @if ($downloadUrl)
            @can('issue', $quote)
                <form action="{{ route('quotes.documents.regenerate', $quote->id) }}" method="POST" onsubmit="return confirm('Tạo lại PDF theo mẫu hiện tại? File PDF cũ sẽ được thay thế.');">
                    @csrf
                    <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        <flux:icon.arrow-path class="size-3.5" />
                        <span>Tạo lại PDF</span>
                    </button>
                </form>
            @endcan
            <a
                href="{{ $downloadUrl }}"
                class="inline-flex h-8 items-center gap-1.5 rounded-md bg-indigo-600 px-3 text-xs font-semibold text-white hover:bg-indigo-500"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.08 48.08 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.656" />
                </svg>
                <span>Tải PDF chính thức</span>
            </a>
            @else
                <span class="rounded-lg bg-slate-200 px-4 py-2 text-xs font-medium text-slate-600">Chưa thể tải PDF</span>
            @endif
            <button
                type="button"
                onclick="window.close()"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
                Đóng
            </button>
        </div>
    </div>

    @if (session()->has('generated_public_url'))
        <div class="no-print mx-auto max-w-4xl mb-6 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-xs">
            <div class="font-bold text-emerald-900">Đã phát hành Link xem báo giá công khai cho Khách hàng:</div>
            <div class="mt-2 flex items-center gap-2">
                <input type="text" readonly value="{{ session('generated_public_url') }}" class="w-full rounded border border-emerald-300 bg-white p-2 font-mono text-xs text-slate-800" />
                <button type="button" onclick="navigator.clipboard.writeText('{{ session('generated_public_url') }}'); alert('Đã sao chép đường dẫn!');" class="rounded bg-emerald-700 px-3 py-2 text-white hover:bg-emerald-800 font-semibold shrink-0">
                    Sao chép URL
                </button>
            </div>
            @if (session('public_link_access_protected'))
                <p class="mt-2 text-emerald-800">Link này đã được bảo vệ bằng mã truy cập. Hãy gửi mã đó qua kênh riêng cho khách hàng.</p>
            @endif
        </div>
    @endif

    <!-- Printable Quote Document Card -->
    <div class="print-container relative mx-auto max-w-4xl overflow-hidden rounded-2xl border border-slate-200 bg-white p-8 shadow-sm md:p-12">
        @if (! $downloadUrl)
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-amber-800">
                Bản xem trước — chưa phải báo giá chính thức
            </div>
        @endif
        <!-- Header: Company Info & Document Title -->
        <div class="flex flex-col justify-between gap-6 border-b border-slate-200 pb-8 sm:flex-row sm:items-start">
            <div>
                <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900">BẢNG BÁO GIÁ</h1>
                <p class="mt-1 font-mono text-sm font-semibold text-slate-600">Số: {{ $quote->quote_number }}</p>
                <p class="mt-1 text-xs text-slate-500">Ngày tạo: {{ $quote->created_at?->format('d/m/Y') }} | Hạn hiệu lực: {{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : '—' }}</p>
            </div>

            <div class="text-left sm:text-right">
                <h2 class="text-base font-bold text-slate-900">SALESFLOW CRM</h2>
                <p class="text-xs text-slate-500">Hệ thống Quản trị Bán hàng Doanh nghiệp</p>
                <p class="mt-1 text-xs text-slate-500">Website: https://salesflow.test</p>
                <p class="text-xs text-slate-500">Hotline: 1900 8888 | Email: support@salesflow.test</p>
            </div>
        </div>

        <!-- Client Info & Opportunity Context -->
        <div class="mt-8 grid gap-6 sm:grid-cols-2 rounded-xl border border-slate-100 bg-slate-50/80 p-5 text-xs">
            <div>
                <p class="font-bold uppercase tracking-wider text-slate-400">Đơn vị nhận báo giá (Khách hàng)</p>
                <h3 class="mt-2 text-sm font-bold text-slate-900">{{ $quote->company?->name ?? 'Khách hàng cá nhân' }}</h3>
                <p class="mt-1 text-slate-600">Mã số thuế: <span class="font-mono font-medium">{{ $quote->company?->tax_code ?: '—' }}</span></p>
                <p class="mt-0.5 text-slate-600">Người đại diện: <span class="font-medium text-slate-900">{{ $quote->contact?->full_name ?: 'Chưa chỉ định' }}</span> ({{ $quote->contact?->job_title ?: '—' }})</p>
                <p class="mt-0.5 text-slate-600">Email / SĐT: {{ $quote->contact?->email ?: $quote->company?->email ?: '—' }} / {{ $quote->contact?->phone ?: $quote->company?->phone ?: '—' }}</p>
            </div>

            <div>
                <p class="font-bold uppercase tracking-wider text-slate-400">Dự án / Cơ hội kinh doanh</p>
                <h3 class="mt-2 text-sm font-bold text-slate-900">{{ $quote->opportunity?->title ?? 'Báo giá dịch vụ' }}</h3>
                <p class="mt-1 text-slate-600">Người phụ trách: <span class="font-medium text-slate-900">{{ $quote->creator?->name ?? 'N/A' }}</span></p>
                <p class="mt-0.5 text-slate-600">Phòng ban: {{ $quote->opportunity?->department?->name ?? 'Kinh doanh' }}</p>
            </div>
        </div>

        <!-- Quote Items Table -->
        <div class="mt-8">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Chi tiết Danh mục Sản phẩm / Dịch vụ</h3>
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b-2 border-slate-200 bg-slate-50 text-slate-700">
                        <th class="py-3 px-3 font-bold w-12 text-center">STT</th>
                        <th class="py-3 px-3 font-bold">Tên sản phẩm / Dịch vụ</th>
                        <th class="py-3 px-3 font-bold w-20 text-center">Số lượng</th>
                        <th class="py-3 px-3 font-bold align-end text-right">Đơn giá (VNĐ)</th>
                        <th class="py-3 px-3 font-bold w-20 text-center">KM (%)</th>
                        <th class="py-3 px-3 font-bold align-end text-right">Thành tiền (VNĐ)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($quote->items as $index => $item)
                        <tr>
                            <td class="py-3 px-3 text-center text-slate-500">{{ $index + 1 }}</td>
                            <td class="py-3 px-3 font-medium text-slate-900">
                                <div>{{ $item->product_name }}</div>
                                @if ($item->sku)
                                    <div class="font-mono text-[11px] text-slate-400">SKU: {{ $item->sku }}</div>
                                @endif
                                @if ($item->notes)
                                    <div class="text-[11px] text-slate-500">{{ $item->notes }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center text-slate-700 font-medium">{{ $item->quantity }}</td>
                            <td class="py-3 px-3 text-right text-slate-700">{{ number_format((float) $item->unit_price, 0, ',', '.') }} ₫</td>
                            <td class="py-3 px-3 text-center text-slate-500">{{ (float) $item->discount_percent > 0 ? number_format((float) $item->discount_percent, 0).'%' : '—' }}</td>
                            <td class="py-3 px-3 text-right font-bold text-slate-900">{{ number_format((float) $item->total_price, 0, ',', '.') }} ₫</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">Không có mục sản phẩm nào trong báo giá.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Total Calculation & Terms -->
        <div class="mt-8 border-t border-slate-200 pt-6">
            <div class="flex flex-col gap-6 sm:flex-row sm:justify-between">
                <div class="max-w-md text-xs text-slate-600 space-y-2">
                    <p class="font-bold uppercase tracking-wider text-slate-500">Điều khoản & Ghi chú thanh toán</p>
                    <p class="whitespace-pre-line leading-relaxed">{{ $quote->notes ?: "• Báo giá có giá trị hiệu lực đến ngày ".($quote->valid_until ? $quote->valid_until->format('d/m/Y') : 'hết hạn').".\n• Phương thức thanh toán: Chuyển khoản ngân hàng.\n• Giá chưa bao gồm các chi phí phát sinh bổ sung ngoài danh mục." }}</p>
                </div>

                <div class="w-full sm:w-72 space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Tổng tiền hàng:</span>
                        <span class="font-medium text-slate-900">{{ number_format((float) $quote->subtotal, 0, ',', '.') }} ₫</span>
                    </div>

                    @if ((float) $quote->discount_amount > 0)
                        <div class="flex justify-between py-1 border-b border-slate-100 text-emerald-600">
                            <span>Khuyến mãi tổng đơn:</span>
                            <span class="font-medium">-{{ number_format((float) $quote->discount_amount, 0, ',', '.') }} ₫</span>
                        </div>
                    @endif

                    <div class="flex justify-between py-1.5 border-b border-slate-200 font-medium text-slate-800">
                        <span>Thuế GTGT (VAT {{ number_format((float) $quote->tax_percent, 0) }}%):</span>
                        <span class="font-bold text-slate-900">+{{ number_format((float) $quote->tax_amount, 0, ',', '.') }} ₫</span>
                    </div>

                    <div class="flex justify-between py-2 border-t-2 border-slate-900 text-sm font-bold">
                        <span class="text-slate-900">TỔNG CỘNG THANH TOÁN:</span>
                        <span class="text-indigo-600">{{ number_format((float) $quote->total_amount, 0, ',', '.') }} ₫</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signatures Section -->
        <div class="mt-16 grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <p class="font-bold text-slate-900 uppercase">ĐẠI DIỆN KHÁCH HÀNG</p>
                <p class="text-[11px] text-slate-400 italic">(Ký, ghi rõ họ tên và đóng dấu)</p>
                <div class="h-20"></div>
                <p class="font-semibold text-slate-700">{{ $quote->contact?->full_name ?? '—' }}</p>
            </div>

            <div>
                <p class="font-bold text-slate-900 uppercase">ĐẠI DIỆN SALESFLOW CRM</p>
                <p class="text-[11px] text-slate-400 italic">(Ký, ghi rõ họ tên và đóng dấu)</p>
                <div class="h-20"></div>
                <p class="font-semibold text-slate-700">{{ $quote->creator?->name ?? 'Người lập báo giá' }}</p>
            </div>
        </div>
    </div>
</body>
</html>
