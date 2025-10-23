<h2>Có tài khoản mới cần xét duyệt</h2>
<p><b>Họ tên:</b> {{ $user->full_name }}</p>
<p><b>Email:</b> {{ $user->email }}</p>
<p><b>Loại tài khoản:</b> {{ ucfirst($user->account_type) }}</p>

<p>Bạn có thể xem chi tiết và duyệt tại:</p>
<a href="{{ $adminUrl }}" target="_blank"
   style="display:inline-block;padding:8px 12px;background:#2f6feb;color:#fff;border-radius:4px;text-decoration:none;">
   Duyệt tài khoản
</a>
