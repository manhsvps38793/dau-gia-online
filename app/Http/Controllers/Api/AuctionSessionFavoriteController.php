<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionSessionFavorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuctionSessionFavoriteController extends Controller
{
    // ✅ Thêm hoặc bỏ quan tâm
    public function toggleFavorite($sessionId)
    {
        $userId = Auth::id();

        $favorite = AuctionSessionFavorite::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['status' => true, 'message' => 'Đã bỏ quan tâm phiên đấu giá.']);
        } else {
            AuctionSessionFavorite::create([
                'user_id' => $userId,
                'session_id' => $sessionId
            ]);
            return response()->json(['status' => true, 'message' => 'Đã thêm vào danh sách quan tâm.']);
        }
    }

    // ✅ Lấy danh sách các phiên đã quan tâm của người dùng
    public function myFavorites()
    {
        $userId = Auth::id();

        $favorites = AuctionSessionFavorite::with('session.item')
            ->where('user_id', $userId)
            ->get();

        return response()->json([
            'status' => true,
            'favorites' => $favorites
        ]);
    }
}
