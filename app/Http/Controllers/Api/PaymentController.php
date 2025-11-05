<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    // ===================== 🔹 Tạo thanh toán nội bộ =====================
    public function makePayment(Request $request, $contract_id)
    {
        $contract = Contract::find($contract_id);
        if (!$contract || $contract->status !== 'ChoThanhToan') {
            return response()->json(['status' => false, 'message' => 'Hợp đồng không hợp lệ'], 400);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string'
        ]);

        if ($request->amount < $contract->final_price) {
            return response()->json(['status' => false, 'message' => "Số tiền phải >= {$contract->final_price}"], 400);
        }

        DB::transaction(function () use ($request, $contract) {
            $payment = Payment::create([
                'contract_id'  => $contract->contract_id,
                'profile_id'   => $contract->profile_id,
                'sender_id'    => $request->user()->user_id,
                'receiver_id'  => optional($contract->session)->auction_org_id,
                'amount'       => $request->amount,
                'payment_date' => now(),
                'method'       => $request->method,
                'status'       => 'HoanTat'
            ]);

            $contract->update(['status' => 'DaThanhToan']);

            $notification = Notification::create([
                'user_id' => $contract->winner_id,
                'type'    => 'ThanhToan',
                'message' => "Thanh toán hợp đồng #{$contract->contract_id} thành công!"
            ]);

            event(new \App\Events\NotificationCreated($notification));
        });

        return response()->json(['status' => true, 'message' => 'Thanh toán thành công']);
    }

    // ===================== 🔹 Danh sách thanh toán =====================
    public function listPayments(Request $request)
    {
        $payments = Payment::with(['contract.session'])
            ->orderBy('payment_id', 'desc')
            ->get();
        return response()->json($payments);
    }

    // ===================== 🔹 Xem chi tiết thanh toán =====================
    public function show($id)
    {
        $payment = Payment::with(['contract.session'])->find($id);
        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy thanh toán'], 404);
        }
        return response()->json(['status' => true, 'data' => $payment]);
    }

    // ===================== 🔹 Cập nhật thanh toán =====================
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy thanh toán'], 404);
        }

        $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'method' => 'nullable|string',
            'status' => 'nullable|string|in:ChoXuLy,HoanTat,Huy'
        ]);

        $payment->update($request->only(['amount', 'method', 'status']));

        return response()->json(['status' => true, 'message' => 'Cập nhật thành công', 'data' => $payment]);
    }

    // ===================== 🔹 Xóa thanh toán =====================
    public function destroy($id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy thanh toán'], 404);
        }

        $payment->delete();

        return response()->json(['status' => true, 'message' => 'Xóa thanh toán thành công']);
    }

    // ===================== 🔹 Thanh toán online VNPAY =====================
    public function payOnline(Request $request, $contract_id)
    {
        $contract = Contract::find($contract_id);
        if (!$contract || $contract->status !== 'ChoThanhToan') {
            return response()->json(['status' => false, 'message' => 'Hợp đồng không hợp lệ'], 400);
        }

        $vnp_TmnCode = config('vnpay.vnp_TmnCode');
        $vnp_HashSecret = config('vnpay.vnp_HashSecret');
        $vnp_Url = config('vnpay.vnp_Url');
        $vnp_Returnurl = config('vnpay.vnp_PaymentReturnUrl');

        $vnp_TxnRef = time();
        $vnp_Amount = $contract->final_price * 100;

        $inputData = [
            "vnp_Version"   => "2.1.0",
            "vnp_Command"   => "pay",
            "vnp_TmnCode"   => $vnp_TmnCode,
            "vnp_Amount"    => $vnp_Amount,
            "vnp_CreateDate"=> date('YmdHis'),
            "vnp_CurrCode"  => "VND",
            "vnp_IpAddr"    => $request->ip(),
            "vnp_Locale"    => "vn",
            "vnp_OrderInfo" => "Thanh toan hop dong #".$contract_id,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef"    => $vnp_TxnRef
        ];

        if ($request->bank_code) $inputData['vnp_BankCode'] = $request->bank_code;

        ksort($inputData);
        $query = "";
        $hashdata = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            $hashdata .= ($i++ > 0 ? '&' : '') . urlencode($key) . '=' . urlencode($value);
            $query .= urlencode($key) . '=' . urlencode($value) . '&';
        }

        $vnp_Url .= "?" . $query . 'vnp_SecureHash=' . hash_hmac('sha512', $hashdata, $vnp_HashSecret);

        return response()->json(['status' => true, 'payment_url' => $vnp_Url]);
    }

    // ===================== 🔹 Nhận callback VNPAY =====================
    public function vnpayReturn(Request $request)
    {
        $inputData = $request->all();
        $vnp_HashSecret = config('vnpay.vnp_HashSecret');
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
        ksort($inputData);
        $hashData = http_build_query($inputData);
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash !== $vnp_SecureHash) {
            return response()->json(['status' => false, 'message' => 'Sai checksum!']);
        }

        if (($inputData['vnp_ResponseCode'] ?? '') !== '00') {
            return response()->json(['status' => false, 'message' => 'Thanh toán thất bại!']);
        }

        $contract_id = explode('#', $inputData['vnp_OrderInfo'])[1] ?? null;
        if (!$contract_id) return response()->json(['status' => false, 'message' => 'Không xác định hợp đồng']);

        $contract = Contract::with('session')->find($contract_id);
        if (!$contract) return response()->json(['status' => false, 'message' => 'Hợp đồng không tồn tại']);

        // Tránh duplicate
        $exists = Payment::where('contract_id', $contract_id)->where('method', 'VNPAY')->first();
        if ($exists) return response()->json(['status' => true, 'message' => 'Giao dịch đã được xử lý trước đó']);

        DB::transaction(function () use ($contract) {
            Payment::create([
                'contract_id'  => $contract->contract_id,
                'profile_id'   => $contract->profile_id,
                'sender_id'    => $contract->winner_id,
                'receiver_id'  => optional($contract->session)->auction_org_id,
                'amount'       => $contract->final_price,
                'payment_date' => now(),
                'method'       => 'VNPAY',
                'status'       => 'HoanTat'
            ]);

            $contract->update(['status' => 'DaThanhToan']);

            $notification = Notification::create([
                'user_id' => $contract->winner_id,
                'type'    => 'ThanhToan',
                'message' => "Thanh toán VNPAY hợp đồng #{$contract->contract_id} thành công!"
            ]);
            event(new \App\Events\NotificationCreated($notification));
        });

        return redirect('http://localhost:3000');
    }
    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'contract_id' => 'required|integer|exists:contracts,contract_id',
            'method' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'status' => 'required|string',
        ]);

        $payment = \App\Models\Payment::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Thêm thanh toán thành công!',
            'data' => $payment,
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Lỗi khi lưu thanh toán: ' . $e->getMessage(),
        ], 500);
    }
}

}
