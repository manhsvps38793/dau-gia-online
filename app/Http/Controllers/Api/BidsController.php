<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bid;
use App\Models\AuctionSession;
use App\Models\Notification;
use App\Events\BidPlaced;

class BidsController extends Controller
{
    public function placeBid(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:AuctionSessions,session_id',
            'amount' => 'required|numeric|min:1'
        ]);

        $user = $request->user();
        $session = AuctionSession::with('item')->find($request->session_id);

        if (!$session || $session->status !== 'DangDienRa') {
            return response()->json(['status'=>false,'message'=>'Phiên đấu giá chưa mở hoặc đã kết thúc'],400);
        }

        // Kiểm tra user đã được duyệt hồ sơ
        $profile = $user->auctionProfiles()
            ->where('item_id', $session->item_id)
            ->where('status', 'DaDuyet')
            ->first();

        if (!$profile) {
            return response()->json(['status'=>false,'message'=>'Bạn chưa được duyệt tham gia phiên đấu giá này'],403);
        }

        $highestBid = Bid::where('session_id', $session->session_id)
                         ->orderBy('amount','desc')
                         ->first();
        $minBid = $highestBid ? $highestBid->amount + 1 : $session->item->starting_price;

        if ($request->amount < $minBid) {
            return response()->json(['status'=>false,'message'=>"Giá đặt phải >= {$minBid}"],400);
        }

        $bid = Bid::create([
            'session_id'=>$session->session_id,
            'user_id'=>$user->user_id,
            'amount'=>$request->amount,
            'bid_time'=>now()
        ]);

        // Tạo thông báo
        Notification::create([
            'user_id' => $user->user_id,
            'message' => "Bạn đã đặt giá {$request->amount} cho phiên #{$session->session_id}",
            'created_at' => now()
        ]);

        // Broadcast realtime
        broadcast(new BidPlaced($bid))->toOthers();

        return response()->json(['status'=>true,'message'=>'Đặt giá thành công','bid'=>$bid]);
    }

    // API lấy danh sách bid hiện tại
    public function listBids($sessionId)
    {
        $bids = Bid::where('session_id', $sessionId)
                   ->orderBy('bid_time', 'desc')
                   ->get();
        return response()->json($bids);
    }
}
