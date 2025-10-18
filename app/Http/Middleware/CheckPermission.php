<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        foreach ($permissions as $permission) {
            if (!$user->hasPermission($permission)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không có quyền truy cập'
                ], 403);
            }
        }

        return $next($request);
    }
}
