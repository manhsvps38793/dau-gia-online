<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuctionSession;
use App\Models\AuctionSessionFavorite; // ✅ THÊM
use App\Models\contract;
use App\Models\EContracts;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Jobs\StartAuctionJob;
use App\Jobs\EndAuctionJob;
use App\Events\AuctionSessionUpdated;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;

class AuctionSessionController extends Controller
{
    // ========================== CREATE ==========================
    
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Validate dữ liệu
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
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Tạo phiên đấu giá
        $session = AuctionSession::create(array_merge($request->all(), [
            'created_by' => $user->user_id
        ]));
        $session->auctioneer_id = $request->auctioneer_id ?? null;

        $now = now();

        // 3. Lên job bắt đầu/kết thúc đấu giá
        if (Carbon::parse($session->bid_start)->gt($now)) {
            StartAuctionJob::dispatch($session->session_id)->delay(Carbon::parse($session->bid_start));
        }

        if (Carbon::parse($session->bid_end)->gt($now)) {
            EndAuctionJob::dispatch($session->session_id)->delay(Carbon::parse($session->bid_end));
        }

        // 4. Tạo hợp đồng gốc DichVuDauGia
        $contract = contract::create([
            'session_id' => $session->session_id,
            'winner_id'  => null,
            'final_price' => 0,
            'status'     => 'ChoThanhToan'
        ]);

        // 5. Sinh file PDF hợp đồng
        $pdfData = [
            'session'  => $session,
            'contract' => $contract,
            'owner'    => $session->auction_org_id,
            'auction_org' => User::find($session->auction_org_id),
            'defaultFont' => 'sans-serif',
        ];

        $pdf = PDF::loadView('contracts.dichvu_template', $pdfData)
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Đặt đường dẫn lưu vào public disk
        $fileName = 'contracts/contract_session_' . $session->session_id . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());
        $fileUrl = Storage::url($fileName);

        // 6. Tạo hợp đồng điện tử
        $econtract = EContracts::create([
            'contract_type' => 'DichVuDauGia',
            'file_url'      => $fileUrl,
            'signed_by'     => $session->auction_org_id,
            'session_id'    => $session->session_id,
            'contract_id'   => $contract->contract_id
        ]);

        $ownerId = $session->item->user_id ?? null;

        // 7. Gửi thông báo
        if ($ownerId) {
            $notification = Notification::create([
                'user_id' => $ownerId,
                'message' => 'Bạn có hợp đồng dịch vụ đấu giá mới cần ký: '
            ]);
        }

        $notification = Notification::create([
            'user_id' => $session->auction_org_id,
            'message' => "{$request->user()->full_name}Có hợp đồng dịch vụ đấu giá mới cần xử lý: "
        ]);

        // 8. Trigger event nếu cần realtime
        event(new \App\Events\NotificationCreated($notification));
        event(new AuctionSessionUpdated($session));

        return response()->json([
            'status'  => true,
            'message' => 'Tạo phiên đấu giá và hợp đồng dịch vụ thành công',
            'session' => $session
        ]);
    }

    // ========================== READ ==========================

   // ✅ SỬA METHOD NÀY
    public function index()
    {
        $sessions = AuctionSession::with([
            'item.owner',
            'auctioneer',
            'auctionOrg',
            'profiles.user',
            'favorites' // ✅ THÊM relation này
        ])
        ->orderBy('session_id', 'desc')
        ->get();

        return response()->json([
            'status' => true,
            'sessions' => $sessions
        ]);
    }

    // ✅ SỬA METHOD NÀY
    public function show($id)
    {
        $session = AuctionSession::with([
            'item.owner',
            'auctioneer',
            'auctionOrg',
            'profiles.user',
            'favorites' // ✅ THÊM
        ])
        ->findOrFail($id);

        return response()->json([
            'status' => true,
            'session' => $session
        ]);
    }

    // ========================== UPDATE ==========================

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
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $session->auctioneer_id = $request->auctioneer_id ?? null;
        $session->update($request->all());

        $now = now();

        if ($session->bid_start->gt($now)) {
            StartAuctionJob::dispatch($session->session_id)->delay($session->bid_start);
        }

        if ($session->bid_end->gt($now)) {
            EndAuctionJob::dispatch($session->session_id)->delay($session->bid_end);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật phiên đấu giá thành công',
            'session' => $session
        ]);
    }

    // ========================== DELETE ==========================

    public function destroy($sessionId)
    {
        $session = AuctionSession::where('session_id', $sessionId)->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Xóa phiên đấu giá thành công'
        ]);
    }

    // ========================== PAUSE/RESUME ==========================

    public function pause($id)
    {
        $session = AuctionSession::findOrFail($id);

        if ($session->paused) {
            return response()->json(['message' => 'Phiên đã tạm dừng rồi'], 400);
        }

        $now = Carbon::now();
        $remaining = $now->diffInSeconds($session->bid_end, false);

        if ($remaining <= 0) {
            return response()->json(['message' => 'Phiên đã kết thúc'], 400);
        }

        $session->paused = true;
        $session->paused_at = $now;
        $session->remaining_time = $remaining;
        $session->save();

        event(new AuctionSessionUpdated($session));

        return response()->json([
            'message' => 'Đã tạm dừng phiên đấu giá',
            'remaining_seconds' => $remaining,
        ]);
    }

    public function resume($id)
    {
        $session = AuctionSession::findOrFail($id);

        if (!$session->paused) {
            return response()->json(['message' => 'Phiên không ở trạng thái tạm dừng'], 400);
        }

        $pausedAt = Carbon::parse($session->paused_at);
        $endAt = Carbon::parse($session->bid_end);
        $remaining = $session->remaining_time ?? $endAt->diffInSeconds($pausedAt, false);

        if ($remaining <= 0) {
            return response()->json(['message' => 'Phiên đã hết thời gian'], 400);
        }

        $newEnd = Carbon::now()->addSeconds($remaining);

        $session->update([
            'paused' => false,
            'paused_at' => null,
            'bid_end' => $newEnd,
            'remaining_time' => null,
        ]);

        EndAuctionJob::dispatch($session->session_id)->delay($newEnd);
        event(new AuctionSessionUpdated($session));

        return response()->json([
            'message' => 'Đã tiếp tục phiên đấu giá',
            'new_bid_end' => $newEnd,
        ]);
    }

    // ========================== KICK USER ==========================

    public function kickUser(Request $request, $sessionId, $userId)
    {
        $session = AuctionSession::find($sessionId);
        if (!$session) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy phiên đấu giá'
            ], 404);
        }

        $authUser = $request->user();

        $profile = \App\Models\AuctionProfile::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$profile) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng này không tham gia phiên đấu giá'
            ], 404);
        }

        $reason = $request->input('reason', 'Gian lận đấu giá');
        $updated = $profile->update([
            'is_kicked'   => true,
            'kick_reason' => $reason,
            'status'      => 'BịTuChoi',
        ]);

        event(new \App\Events\AuctionSessionUpdated($session));

        return response()->json([
            'status'  => $updated,
            'message' => $updated
                ? 'Đã kích người dùng ra khỏi phiên đấu giá'
                : 'Cập nhật trạng thái bị kick thất bại',
            'profile' => $profile->fresh(),
        ]);
    }
}