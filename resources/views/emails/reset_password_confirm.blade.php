<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đặt lại mật khẩu</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f4f6f8;
      margin: 0;
      padding: 0;
      color: #333;
    }
    .container {
      max-width: 600px;
      margin: 40px auto;
      background: #ffffff;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    .header {
      text-align: center;
      padding-bottom: 20px;
      border-bottom: 2px solid #007bff;
    }
    .header h1 {
      color: #007bff;
    }
    .content {
      margin-top: 25px;
      line-height: 1.6;
      font-size: 16px;
    }
    .button {
      display: block;
      width: fit-content;
      margin: 30px auto;
      padding: 14px 28px;
      background-color: #007bff;
      color: #fff !important;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      letter-spacing: 0.3px;
    }
    .button:hover {
      background-color: #0056b3;
    }
    .footer {
      text-align: center;
      font-size: 13px;
      color: #888;
      margin-top: 30px;
      border-top: 1px solid #eee;
      padding-top: 20px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2>🔒 Xác nhận đổi mật khẩu</h2>
    </div>

    <div class="content">
      <p>Xin chào <strong>{{ $fullName }}</strong>,</p>

      <p>Chúng tôi đã nhận được yêu cầu <strong>đổi mật khẩu</strong> cho tài khoản của bạn.</p>

      <p>Vui lòng nhấn vào nút bên dưới để <strong>xác nhận và đặt lại mật khẩu mới</strong>:</p>

      <a href="{{ $resetUrl }}" class="button">🔑 Đặt lại mật khẩu</a>

      <p>Nếu bạn không yêu cầu thay đổi mật khẩu, vui lòng bỏ qua email này.</p>

      <p>Liên kết sẽ hết hạn sau <strong>60 phút</strong> để đảm bảo an toàn.</p>
    </div>

    <div class="footer">
      <p>Cảm ơn bạn đã tin tưởng và sử dụng hệ thống của chúng tôi!</p>
      <p>Trân trọng,<br><strong>{{ config('app.name') }}</strong></p>
    </div>
  </div>
</body>
</html>
