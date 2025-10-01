<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionProfile;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuctionProfileController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id'       => 'required|exists:AuctionItems,item_id',
            'document_url'  => 'required|string',
            'deposit_amount'=> 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'=> 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = AuctionProfile::create([
            'user_id'       => $request->user()->user_id,
            'item_id'       => $request->item_id,
            'document_url'  => $request->document_url,
            'deposit_amount'=> $request->deposit_amount,
            'status'        => 'ChoDuyet',
            'created_at'    => now()
        ]);
         // ✅ Tạo thông báo khi nộp hồ sơ
        Notification::create([
            'user_id' => $request->user()->user_id,
            'type' => 'HoSo',
            'message' => "Bạn đã nộp hồ sơ đấu giá cho tài sản #{$request->item_id}, chờ duyệt.",
            'created_at' => now()
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Đăng ký tham gia đấu giá thành công',
            'profile' => $profile
        ]);
    }
    public function updateStatus(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'status' => 'required|in:ChoDuyet,DaDuyet,TuChoi'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message'=> 'Trạng thái không hợp lệ',
            'errors' => $validator->errors()
        ], 422);
    }

    $profile = AuctionProfile::findOrFail($id);
    $profile->status = $request->status;
    $profile->save();

    // 🔔 Gửi thông báo cho user
    Notification::create([
        'user_id' => $profile->user_id,
        'type' => 'HoSo',
        'message' => "Hồ sơ đấu giá cho tài sản #{$profile->item_id} đã được cập nhật trạng thái: {$request->status}.",
        'created_at' => now()
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Cập nhật trạng thái thành công',
        'profile' => $profile
    ]);
}

}
