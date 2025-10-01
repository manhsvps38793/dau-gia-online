<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\Contract;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // Thanh toán hợp đồng
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
            'payer_id'    => $request->user()->user_id,
            'amount'      => $request->amount,
            'payment_date'=> now(),
            'method'      => $request->method,
            'status'      => 'HoanTat'
        ]);

        // Cập nhật trạng thái hợp đồng
        $contract->status = 'DaThanhToan';
        $contract->save();
        // tạo thông báo
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

    // Lấy danh sách thanh toán của user
    public function listPayments(Request $request)
    {
        $payments = Payment::with('contract')
            ->where('payer_id', $request->user()->user_id)
            ->get();

        return response()->json($payments);
    }
}
