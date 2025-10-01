<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Lấy danh sách thông báo theo user
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

    // Tạo thông báo mới
    public function createNotification(Request $request)
    {
        $notification = Notification::create([
            'user_id'   => $request->user_id,
            'type'      => $request->type,
            'message'   => $request->message,
            'is_read'   => 0,
            'created_at'=> now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Thông báo đã được tạo',
            'notification' => $notification
        ]);
    }

    // Đánh dấu 1 thông báo đã đọc
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

    // Đánh dấu tất cả đã đọc
    public function markAllAsRead($user_id)
    {
        Notification::where('user_id', $user_id)->update(['is_read' => 1]);

        return response()->json(['status' => true, 'message' => 'Tất cả thông báo đã đọc']);
    }
}
