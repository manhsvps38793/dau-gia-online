@component('mail::message')
# Chúc mừng {{ $user->name }}!

Bạn đã thắng phiên đấu giá **{{ $session->item->name ?? 'Sản phẩm' }}**.

- Giá thắng: **{{ number_format($session->bids()->orderByDesc('amount')->first()->amount, 0, ',', '.') }} đ**
- Thời gian xác nhận: {{ now()->format('d/m/Y H:i') }}

Cảm ơn bạn đã tham gia cùng chúng tôi!

@component('mail::button', ['url' => url('/my-bids')])
Xem chi tiết
@endcomponent

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent
