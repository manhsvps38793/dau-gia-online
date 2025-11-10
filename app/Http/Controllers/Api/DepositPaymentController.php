<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DepositPayment;
use App\Models\AuctionProfile;
use Illuminate\Support\Facades\DB;

class DepositPaymentController extends Controller
{
    public function pay(Request $request)
    {
        $request->validate([
            'profile_id' => 'required|integer|exists:auctionprofiles,profile_id',
            'amount' => 'required|numeric|min:1000',
            'receiver_id' => 'required|integer|exists:users,user_id',
            'session_id' => 'required|integer'
        ]);

        // ✅ Tạo giao dịch và lưu luôn session_id vào DB
        $deposit = DepositPayment::create([
            'profile_id' => $request->profile_id,
            'amount' => $request->amount,
            'receiver_id' => $request->receiver_id,
            'session_id' => $request->session_id, // 💾 Lưu session_id
            'payment_method' => 'VNPAY',
            'status' => 'ChoXuLy'
        ]);

        // Cấu hình VNPAY
        $vnp_TmnCode   = config('vnpay.vnp_TmnCode');
        $vnp_HashSecret= config('vnpay.vnp_HashSecret');
        $vnp_Url       = config('vnpay.vnp_Url');
        $vnp_Returnurl = config('vnpay.vnp_DepositReturnUrl');

        $vnp_TxnRef = $deposit->deposit_id;
        $vnp_Amount = $deposit->amount * 100; // VNPAY nhận số tiền *100

        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_Command"    => "pay",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => $vnp_Amount,
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode"   => "VND",
            "vnp_IpAddr"     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "vnp_Locale"     => "vn",
            "vnp_OrderInfo"  => "Thanh toan tien coc #" . $vnp_TxnRef,
            "vnp_OrderType"  => "deposit",
            "vnp_ReturnUrl"  => $vnp_Returnurl,
            "vnp_TxnRef"     => $vnp_TxnRef
        ];

        ksort($inputData);

        $hashData = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            $hashData .= ($i++ > 0 ? '&' : '') . urlencode($key) . '=' . urlencode($value);
        }

        $vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        $vnp_Url .= '?' . http_build_query($inputData) . '&vnp_SecureHash=' . $vnpSecureHash;

        return response()->json([
            'status' => true,
            'payment_url' => $vnp_Url,
            'deposit_id' => $deposit->deposit_id,
            'session_id' => $deposit->session_id
        ]);
    }

    // 2️⃣ Nhận callback từ VNPAY cho tiền cọc
    public function vnpayReturn(Request $request)
    {
        $vnp_HashSecret = config('vnpay.vnp_HashSecret');
        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
        ksort($inputData);

        $hashData = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            $hashData .= ($i++ > 0 ? '&' : '') . urlencode($key) . '=' . urlencode($value);
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash !== $vnp_SecureHash) {
            return response()->json([
                'status' => false,
                'message' => 'Chữ ký không hợp lệ!'
            ]);
        }

        $depositId = $inputData['vnp_TxnRef'] ?? null;
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? null;

        if (!$depositId) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy mã giao dịch!'
            ]);
        }

        $deposit = DepositPayment::find($depositId);
        if (!$deposit) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy giao dịch tiền cọc trong hệ thống!'
            ]);
        }

        // ✅ Nếu giao dịch thành công
        if ($vnp_ResponseCode == '00') {
            if ($deposit->status !== 'HoanTat') {
                DB::transaction(function() use ($deposit) {
                    $deposit->update([
                        'status' => 'HoanTat',
                        'payment_date' => now()
                    ]);

                    $profile = AuctionProfile::find($deposit->profile_id);
                    if ($profile) {
                        $profile->update(['status' => 'DaThanhToan']);
                    }
                });
            }

            // 🔥 Giờ redirect bằng session_id lưu trong DB
            // return redirect('http://localhost:3000/detail/' . $deposit->session_id);
            return redirect(env('FRONTEND_URL') . '/detail/' . $deposit->session_id);
        }

        return response()->json([
            'status' => false,
            'message' => 'Giao dịch thất bại hoặc bị hủy!',
            'response_code' => $vnp_ResponseCode
        ]);
    }

    // 3️⃣ Hoàn tiền khi thua đấu giá
    public function refund(Request $request)
    {
        $request->validate([
            'profile_id' => 'required|exists:auctionprofiles,profile_id'
        ]);

        $deposit = DepositPayment::where('profile_id', $request->profile_id)->first();
        if (!$deposit) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy tiền cọc'], 404);
        }

        if ($deposit->status !== 'HoanTat') {
            return response()->json(['status' => false, 'message' => 'Cọc chưa được thanh toán hoàn tất'], 400);
        }

        $deposit->update([
            'refund_status' => 'DaHoan',
            'refund_date' => now()
        ]);

        return response()->json(['status' => true, 'message' => 'Đã hoàn tiền cọc']);
    }

    // 4️⃣ Kiểm tra trạng thái cọc
    public function status($profile_id)
    {
        $deposit = DepositPayment::where('profile_id', $profile_id)->first();
        if (!$deposit) {
            return response()->json(['status' => false, 'message' => 'Chưa có tiền cọc']);
        }

        return response()->json([
            'status' => true,
            'deposit' => $deposit
        ]);
    }
}
