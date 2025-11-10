<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    /**
     * GET /api/activity-logs
     * Lấy danh sách tất cả log, có thể filter theo user hoặc action
     */
    public function index(Request $request)
    {
        $logs = ActivityLog::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $logs
        ]);
    }

    /**
     * GET /api/activity-logs/{id}
     * Xem chi tiết 1 log
     */
    public function show($id)
    {
        $log = ActivityLog::find($id);

        if (!$log) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy log'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $log
        ]);
    }

    /**
     * DELETE /api/activity-logs/{id}
     * Xóa 1 log (nếu cần)
     */
    public function destroy($id)
    {
        $log = ActivityLog::find($id);

        if (!$log) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy log'
            ], 404);
        }

        $log->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa log thành công'
        ]);
    }
}
