<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuctionSession;
use Illuminate\Support\Facades\Validator;

class AuctionSessionController extends Controller
{
    // 📌 Tạo phiên đấu giá
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'item_id'        => 'required|exists:AuctionItems,item_id',
            'start_time'     => 'required|date',
            'end_time'       => 'required|date|after:start_time',
            'regulation'     => 'required|string',
            'status'         => 'in:Mo,DangDienRa,KetThuc',
            'method'         => 'required|in:Đấu giá tự do,Đấu giá kín',
            'auction_org_id' => 'required|exists:Users,user_id',
            'register_start' => 'required|date|before:register_end',
            'register_end'   => 'required|date|after:register_start',
            'checkin_time'   => 'required|date|after_or_equal:register_end',
            'bid_start'      => 'required|date|after:checkin_time',
            'bid_end'        => 'required|date|after:bid_start',
            'bid_step'       => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'=> 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $session = AuctionSession::create(array_merge($request->all(), [
            'created_by' => $user->user_id
        ]));

        return response()->json([
            'status'  => true,
            'message' => 'Tạo phiên đấu giá thành công',
            'session'=> $session
        ]);
    }

    // 📌 Xem danh sách tất cả phiên đấu giá
    public function index()
    {
        $sessions = AuctionSession::with(['item', 'auctionOrg'])->get();
        return response()->json([
            'status' => true,
            'sessions' => $sessions
        ]);
    }

    // 📌 Xem chi tiết 1 phiên đấu giá
    public function show($id)
    {
        $session = AuctionSession::with(['item', 'auctionOrg'])->findOrFail($id);
        return response()->json([
            'status' => true,
            'session' => $session
        ]);
    }

    // 📌 Cập nhật phiên đấu giá
    public function update(Request $request, $id)
    {
        $session = AuctionSession::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item_id'        => 'sometimes|exists:AuctionItems,item_id',
            'start_time'     => 'sometimes|date',
            'end_time'       => 'sometimes|date|after:start_time',
            'regulation'     => 'sometimes|string',
            'status'         => 'sometimes|in:Mo,DangDienRa,KetThuc',
            'method'         => 'sometimes|in:Đấu giá tự do,Đấu giá kín',
            'auction_org_id' => 'sometimes|exists:Users,user_id',
            'register_start' => 'sometimes|date|before:register_end',
            'register_end'   => 'sometimes|date|after:register_start',
            'checkin_time'   => 'sometimes|date|after_or_equal:register_end',
            'bid_start'      => 'sometimes|date|after:checkin_time',
            'bid_end'        => 'sometimes|date|after:bid_start',
            'bid_step'       => 'sometimes|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'=> 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $session->update($request->all());

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật phiên đấu giá thành công',
            'session'=> $session
        ]);
    }

    // 📌 Xóa phiên đấu giá
    public function destroy($id)
    {
        $session = AuctionSession::findOrFail($id);
        $session->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Xóa phiên đấu giá thành công'
        ]);
    }
}
