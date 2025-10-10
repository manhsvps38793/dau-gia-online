<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\Contract;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // ===================== 🔹 Thanh toán thủ công (nội bộ) =====================
    public function makePayment(Request $request, $contract_id)
    {
        $contract = Contract::find($contract_id);

        if (!$contract || $contract->status !== 'ChoThanhToan') {
            return response()->json([
                'status'=>false,
                'message'=>'Hợp đồng không tồn tại hoặc không cần thanh toán'
            ], 400);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string'
        ]);

        if ($request->amount < $contract->final_price) {
            return response()->json([
                'status'=>false,
                'message'=>"Số tiền phải >= {$contract->final_price}"
            ], 400);
        }

        $payment = Payment::create([
            'contract_id' => $contract_id,
            'sender_id'    => $request->user()->user_id,
            'amount'      => $request->amount,
            'payment_date'=> now(),
            'method'      => $request->method,
            'status'      => 'HoanTat'
        ]);

        // Cập nhật trạng thái hợp đồng
        $contract->status = 'DaThanhToan';
        $contract->save();

        // Tạo thông báo
        Notification::create([
            'user_id' => $contract->winner_id,
            'type' => 'ThanhToan',
            'message' => "Thanh toán cho hợp đồng #{$contract->contract_id} đã hoàn tất"
        ]);

        return response()->json([
            'status'=>true,
            'message'=>'Thanh toán thành công',
            'payment'=>$payment
        ]);
    }

    // ===================== 🔹 Lấy danh sách thanh toán =====================
    public function listPayments(Request $request)
    {
        $payments = Payment::with('contract.session')
            ->where('payer_id', $request->user()->user_id)
            ->orderBy('payment_id', 'desc')
            ->get();

        return response()->json($payments);
    }

    // ===================== 🔹 Thanh toán online qua VNPAY =====================
    public function payOnline(Request $request, $contract_id)
    {
        $contract = Contract::find($contract_id);

        if (!$contract || $contract->status !== 'ChoThanhToan') {
            return response()->json(['status'=>false, 'message'=>'Hợp đồng không hợp lệ'], 400);
        }

        date_default_timezone_set('Asia/Ho_Chi_Minh');

        $vnp_TmnCode = config('vnpay.vnp_TmnCode');
        $vnp_HashSecret = config('vnpay.vnp_HashSecret');
        $vnp_Url = config('vnpay.vnp_Url');
        $vnp_Returnurl = config('vnpay.vnp_Returnurl');

        $vnp_TxnRef = time();
        $vnp_OrderInfo = 'Thanh toan hop dong #' . $contract_id;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $contract->final_price * 100;
        $vnp_Locale = 'vn';
        $vnp_BankCode = $request->bank_code ?? '';
        $vnp_IpAddr = $request->ip();

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        if ($vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        ksort($inputData);
        $query = "";
        $hashdata = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

        return response()->json([
            'status' => true,
            'payment_url' => $vnp_Url
        ]);
    }

    // ===================== 🔹 Nhận phản hồi từ VNPAY (Return URL) =====================
public function vnpayReturn(Request $request)
{
    $inputData = $request->all();

    $vnp_HashSecret = config('vnpay.vnp_HashSecret');
    $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

    // Xóa 2 tham số không cần thiết trước khi hash
    unset($inputData['vnp_SecureHash']);
    unset($inputData['vnp_SecureHashType']);

    // Sắp xếp theo key A-Z
    ksort($inputData);

    // Tạo chuỗi hash
    $hashData = http_build_query($inputData);
    $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

   

    // So khớp checksum
    if ($secureHash === $vnp_SecureHash) {

        // Kiểm tra mã phản hồi giao dịch
        if ($inputData['vnp_ResponseCode'] == '00') {
            $contract_id = explode('#', $inputData['vnp_OrderInfo'])[1] ?? null;

            if ($contract_id) {
                // ✅ Load thêm quan hệ session để lấy auction_org_id
                $contract = Contract::with('session')->find($contract_id);

                if ($contract) {
                    // ✅ Lấy người nhận (auction_org_id từ session)
                    $receiver_id = optional($contract->session)->auction_org_id ?? null;


                    // ✅ Tạo bản ghi thanh toán
                    Payment::create([
                        'contract_id'  => $contract_id,
                        'sender_id'    => $contract->winner_id, // Người thanh toán = người thắng đấu giá
                        'amount'       => $contract->final_price,
                        'payment_date' => now(),
                        'method'       => 'VNPAY',
                        'status'       => 'HoanTat',
                        'receiver_id'  => $receiver_id,         // Người nhận = chủ phiên đấu giá
                    ]);

                    // ✅ Tạo thông báo cho người thắng
                    Notification::create([
                        'user_id' => $contract->winner_id,
                        'type'    => 'ThanhToan',
                        'message' => "Thanh toán qua VNPAY cho hợp đồng #{$contract->contract_id} thành công!"
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Thanh toán thành công!'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Thanh toán thất bại!'
        ]);
    }

    return response()->json([
        'status' => false,
        'message' => 'Sai checksum!'
    ]);
}





}
