<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ data_get($snapshot, 'quote.number') }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10px; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #eef2f7; text-align: left; font-weight: 700; }
        th, td { border: 1px solid #d9e0e8; padding: 7px; }
        .header { border-bottom: 2px solid #1f6f5f; padding-bottom: 14px; margin-bottom: 18px; }
        .brand { font-size: 17px; font-weight: 700; color: #155e51; }
        .title { font-size: 21px; font-weight: 700; text-align: right; }
        .muted { color: #64748b; }
        .right { text-align: right; }
        .center { text-align: center; }
        .section { margin-top: 18px; }
        .total td { border: 0; padding: 4px 7px; }
        .grand { font-size: 13px; font-weight: 700; color: #155e51; border-top: 2px solid #172033 !important; }
        .terms { background: #f8fafc; border: 1px solid #d9e0e8; padding: 10px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="border:0;padding:0;width:58%">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="" style="max-width:130px;max-height:48px;margin-bottom:7px">
                @endif
                <div class="brand">{{ data_get($snapshot, 'branding.company_name', 'SalesFlow CRM') }}</div>
                <div>{{ data_get($snapshot, 'branding.address') }}</div>
                <div>MST: {{ data_get($snapshot, 'branding.tax_code', '—') }} · Hotline: {{ data_get($snapshot, 'branding.hotline', '—') }}</div>
                <div>{{ data_get($snapshot, 'branding.email') }}</div>
            </td>
            <td style="border:0;padding:0" class="right">
                <div class="title">BÁO GIÁ</div>
                <div><strong>{{ data_get($snapshot, 'quote.number') }}</strong> · Phiên bản {{ data_get($snapshot, 'quote.version') }}</div>
                <div class="muted">Hiệu lực đến {{ \Illuminate\Support\Carbon::parse(data_get($snapshot, 'quote.valid_until'))->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:50%">
                <strong>Khách hàng</strong><br>
                {{ data_get($snapshot, 'company.name', '—') }}<br>
                MST: {{ data_get($snapshot, 'company.tax_code', '—') }}<br>
                {{ data_get($snapshot, 'company.address') }}
            </td>
            <td>
                <strong>Người liên hệ</strong><br>
                {{ data_get($snapshot, 'contact.name', '—') }}<br>
                {{ data_get($snapshot, 'contact.email') }}<br>
                {{ data_get($snapshot, 'contact.phone') }}
            </td>
        </tr>
    </table>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th style="width:28px" class="center">STT</th>
                    <th>Sản phẩm / Dịch vụ</th>
                    <th style="width:55px" class="center">SL</th>
                    <th style="width:90px" class="right">Đơn giá</th>
                    <th style="width:55px" class="center">CK</th>
                    <th style="width:100px" class="right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach (data_get($snapshot, 'items', []) as $index => $item)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td><strong>{{ $item['product_name'] }}</strong>@if($item['sku'])<br><span class="muted">SKU: {{ $item['sku'] }}</span>@endif</td>
                        <td class="center">{{ $item['quantity'] }}</td>
                        <td class="right">{{ number_format((float) $item['unit_price'], 0, ',', '.') }}</td>
                        <td class="center">{{ number_format((float) $item['discount_percent'], 2, ',', '.') }}%</td>
                        <td class="right">{{ number_format((float) $item['total_price'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <table class="section total">
        <tr><td style="width:62%"></td><td>Tạm tính</td><td class="right">{{ number_format((float) data_get($snapshot, 'quote.subtotal'), 0, ',', '.') }} ₫</td></tr>
        <tr><td></td><td>Chiết khấu ({{ data_get($snapshot, 'quote.discount_percent') }}%)</td><td class="right">-{{ number_format((float) data_get($snapshot, 'quote.discount_amount'), 0, ',', '.') }} ₫</td></tr>
        <tr><td></td><td>VAT ({{ data_get($snapshot, 'quote.tax_percent') }}%)</td><td class="right">{{ number_format((float) data_get($snapshot, 'quote.tax_amount'), 0, ',', '.') }} ₫</td></tr>
        <tr><td></td><td class="grand">TỔNG CỘNG</td><td class="right grand">{{ number_format((float) data_get($snapshot, 'quote.total_amount'), 0, ',', '.') }} ₫</td></tr>
    </table>
    <p><strong>Bằng chữ:</strong> {{ $totalInWords }}.</p>

    <div class="section terms">
        <strong>Điều khoản thanh toán</strong>
        <p>{!! nl2br(e(data_get($snapshot, 'quote.notes') ?: data_get($snapshot, 'branding.payment_terms', 'Thanh toán theo thỏa thuận giữa hai bên.'))) !!}</p>
        @if(data_get($snapshot, 'branding.bank_information'))
            <p><strong>Thông tin chuyển khoản:</strong><br>{!! nl2br(e(data_get($snapshot, 'branding.bank_information'))) !!}</p>
        @endif
    </div>
</body>
</html>
