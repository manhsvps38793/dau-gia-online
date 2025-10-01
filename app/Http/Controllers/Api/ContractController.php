<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Notification;
use App\Models\AuctionSession;
use App\Models\Bid;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    // Tạo hợp đồng cho phiên đấu giá đã kết thúc
    public function createContract($session_id)
    {
        $session = AuctionSession::find($session_id);

        if (!$session || $session->status != 'KetThuc') {
            return response()->json([
                'status'=>false,
                'message'=>'Phiên đấu giá chưa kết thúc'
            ], 400);
        }

        $highestBid = Bid::where('session_id', $session_id)
                         ->orderBy('amount','desc')
                         ->first();

        if (!$highestBid) {
            return response()->json([
                'status'=>false,
                'message'=>'Không có người thắng'
            ], 400);
        }

        $contract = Contract::create([
            'session_id'   => $session_id,
            'winner_id'    => $highestBid->user_id,
            'final_price'  => $highestBid->amount,
            'signed_date'  => now(),
            'status'       => 'ChoThanhToan'
        ]);
        // tạo thông báo
        Notification::create([
            'user_id' => $highestBid->user_id,
            'type' => 'HopDong',
            'message' => "Bạn đã thắng phiên #{$session_id}, hợp đồng #{$contract->contract_id} đã được tạo"
        ]);
         // ✅ Thông báo cho người thắng
        Notification::create([
            'user_id' => $highestBid->user_id,
            'type' => 'ThangDauGia',
            'message' => "Chúc mừng! Bạn đã thắng phiên đấu giá #{$session_id} với giá {$highestBid->amount}.",
            'created_at' => now()
        ]);

        return response()->json([
            'status'=>true,
            'message'=>'Tạo hợp đồng thành công',
            'contract'=>$contract
        ]);
    }
}
