<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionProfile;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Events\AuctionProfileUpdated;

class AuctionProfileController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validate
        $validator = Validator::make($request->all(), [
            'session_id'     => 'required|exists:AuctionSessions,session_id', // 🔹 sửa từ item_id
            'document_url'   => 'required|file|mimes:pdf,doc,docx|max:2048',
            'deposit_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 2. Upload file
        $filePath = $request->file('document_url')->store('auction_profiles', 'public');

        // 3. Lưu hồ sơ đấu giá
        $profile = AuctionProfile::create([
            'user_id'        => $request->user()->user_id,
            'session_id'     => $request->session_id, // 🔹 sửa từ item_id
            'document_url'   => $filePath,
            'deposit_amount' => $request->deposit_amount,
            'status'         => 'ChoDuyet',
            'created_at'     => now(),
        ]);

        // 4. Tạo thông báo
         $notification =Notification::create([
            'user_id'    => $request->user()->user_id,
            'type'       => 'HoSo',
            'message'    => "Bạn đã nộp hồ sơ đấu giá cho phiên #{$request->session_id}, chờ duyệt.",
            'created_at' => now(),
        ]);

        // 5. 🔹 Broadcast realtime
                event(new \App\Events\NotificationCreated($notification));

        event(new AuctionProfileUpdated($profile));

        // 6. JSON response
        return response()->json([
            'status'  => true,
            'message' => 'Đăng ký tham gia đấu giá thành công',
            'profile' => $profile,
        ]);
    }

  public function index()
{
    $profiles = AuctionProfile::with(['session', 'item', 'user', 'depositPayment'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($profile) {
            $profile->is_paid = $profile->depositPayment && $profile->depositPayment->status === 'HoanTat';
            return $profile;
        });

    return response()->json([
        'status'  => true,
        'message' => 'Danh sách hồ sơ đấu giá',
        'profiles'=> $profiles
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

        // 🔔 Tạo thông báo
         $notification =Notification::create([
            'user_id' => $profile->user_id,
            'type' => 'HoSo',
            'message' => "Hồ sơ đấu giá cho phiên #{$profile->session_id} đã được cập nhật trạng thái: {$request->status}.",
            'created_at' => now()
        ]);

        // 5. 🔹 Broadcast realtime
                event(new \App\Events\NotificationCreated($notification));

        event(new AuctionProfileUpdated($profile));

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật trạng thái thành công',
            'profile' => $profile
        ]);
    }
}
