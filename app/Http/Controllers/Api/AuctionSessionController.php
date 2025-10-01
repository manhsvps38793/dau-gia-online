<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuctionSession;
use Illuminate\Support\Facades\Validator;

class AuctionSessionController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        // Validator
        $validator = Validator::make($request->all(), [
            'item_id'     => 'required|exists:AuctionItems,item_id',
            'start_time'  => 'required|date',
            'end_time'    => 'required|date|after:start_time',
            'regulation'  => 'required|string',
            'status'      => 'in:Mo,DangDienRa,KetThuc'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'=> 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $session = AuctionSession::create([
            'item_id'     => $request->item_id,
            'created_by'  => $user->user_id,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'regulation'  => $request->regulation,
            'status'      => $request->status
        ]);

        return response()->json([
            'status' => true,
            'message'=> 'Tạo phiên đấu giá thành công',
            'session'=> $session
        ]);
    }
}
