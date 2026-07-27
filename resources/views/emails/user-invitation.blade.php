<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lời mời tham gia SalesFlow CRM</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 24px; }
        .container { max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 32px; }
        .logo { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 24px; }
        .btn { display: inline-block; background-color: #0f172a; color: #ffffff !important; font-weight: 600; text-decoration: none; padding: 12px 24px; border-radius: 8px; margin: 24px 0; }
        .footer { font-size: 12px; color: #64748b; margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">SalesFlow CRM</div>
        <h2>Xin chào {{ $invitation->name }},</h2>
        <p>Bạn đã nhận được lời mời tham gia hệ thống SalesFlow CRM từ quản trị viên <strong>{{ $invitation->inviter->name }}</strong>.</p>
        <p><strong>Thông tin tài khoản:</strong></p>
        <ul>
            <li>Email: {{ $invitation->email }}</li>
            <li>Phòng ban: {{ $invitation->department?->name ?: 'Chưa gán' }}</li>
            <li>Vai trò: {{ $invitation->role }}</li>
        </ul>
        <p>Vui lòng nhấp vào liên kết bên dưới để thiết lập mật khẩu của bạn và bắt đầu sử dụng:</p>
        <a href="{{ $acceptUrl }}" class="btn">Chấp nhận lời mời & Tạo mật khẩu</a>
        <p class="footer">
            Liên kết này có hiệu lực trong 7 ngày (đến {{ $invitation->expires_at->format('H:i d/m/Y') }}).<br>
            Nếu bạn không yêu cầu tài khoản này, vui lòng bỏ qua email.
        </p>
    </div>
</body>
</html>
