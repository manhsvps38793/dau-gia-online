@component('mail::message')
# Xin chào {{ $fullName }}

Cảm ơn bạn đã đăng ký tài khoản.
Vui lòng nhấn nút bên dưới để xác thực email của bạn.

@component('mail::button', ['url' => $verifyUrl])
Xác thực tài khoản
@endcomponent

Nếu bạn không thực hiện đăng ký, hãy bỏ qua email này.

Trân trọng,
**Hệ thống Đấu giá Online**
@endcomponent
