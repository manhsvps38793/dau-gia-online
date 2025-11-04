<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // 🟩 1. Lấy tất cả thông báo (dành cho admin)
    public function getUserNotification()
    {
$notifications = Notification::with('user')
    ->orderBy('created_at', 'desc')
    ->get();
        return response()->json([
            'status' => true,
            'notifications' => $notifications
        ]);
    }

    // 🟩 2. Lấy danh sách thông báo theo user
    public function getUserNotifications($user_id)
    {
        $notifications = Notification::where('user_id', $user_id)
                                     ->orderBy('created_at', 'desc')
                                     ->get();

        return response()->json([
            'status' => true,
            'notifications' => $notifications
        ]);
    }

    // 🟩 3. Xem chi tiết 1 thông báo theo ID
    public function show($id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy thông báo'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'notification' => $notification
        ]);
    }

    // 🟨 4. Thêm thông báo mới
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'type' => 'nullable|string|max:255',
            'message' => 'required|string'
        ]);

        $notification = Notification::create([
            'user_id'   => $request->user_id,
            'type'      => $request->type,
            'message'   => $request->message,
            'is_read'   => 0,
            'created_at'=> now()
        ]);

        // 🔥 Phát realtime (nếu có event)
        event(new \App\Events\NotificationCreated($notification));

        return response()->json([
            'status' => true,
            'message' => 'Tạo thông báo thành công',
            'notification' => $notification
        ]);
    }

    // 🟦 5. Cập nhật thông báo
    public function update(Request $request, $id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy thông báo'], 404);
        }

        $notification->update([
            'type' => $request->type ?? $notification->type,
            'message' => $request->message ?? $notification->message,
            'is_read' => $request->is_read ?? $notification->is_read,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật thành công',
            'notification' => $notification
        ]);
    }

    // 🟥 6. Xóa 1 thông báo
    public function destroy($id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy thông báo'], 404);
        }

        $notification->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa thông báo thành công'
        ]);
    }

    // 🟨 7. Đánh dấu 1 thông báo đã đọc
    public function markAsRead($id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json(['status' => false, 'message' => 'Thông báo không tồn tại'], 404);
        }

        $notification->is_read = 1;
        $notification->save();

        return response()->json(['status' => true, 'message' => 'Thông báo đã được đánh dấu là đã đọc']);
    }

    // 🟦 8. Đánh dấu tất cả thông báo đã đọc (theo user)
    public function markAllAsRead($user_id)
    {
        Notification::where('user_id', $user_id)->update(['is_read' => 1]);

        return response()->json(['status' => true, 'message' => 'Tất cả thông báo đã đọc']);
    }
}
