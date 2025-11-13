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
use App\Mail\WinnerNotification;
use Illuminate\Support\Facades\Mail;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class AuctionSessionController extends Controller
{
    protected function logActivity($userId, $action, $modelType = null, $modelId = null, $description = null, $actionType = null)
    {
        ActivityLog::create([
            'user_id'     => $userId,
            'action'      => $action,
            'action_type' => $actionType ?? 'system', // giá trị mặc định nếu không truyền
            'model_type'  => $modelType ?? 'user',
            'model_id'    => $modelId,
            'description' => $description,
            'created_at'  => now(),
        ]);
    }
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
        $this->logActivity(
            $user->user_id,
            'create',
            'auction_session',
            $session->session_id,
            "{$request->user()->full_name}Tạo phiên đấu giá cho item_id={$request->item_id}, phương thức={$request->method}, thời gian từ {$request->bid_start} đến {$request->bid_end}",
            'user_action'
        );
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
            'favorites'
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
        $old = $session->getOriginal();
        $session->auctioneer_id = $request->auctioneer_id ?? null;
        $session->update($request->all());
        // 🔹 Log cập nhật phiên đấu giá
        $this->logActivity(
            $request->user()->user_id,
            'update',
            'auction_session',
            $session->session_id,
            "{$request->user()->full_name}Cập nhật phiên đấu giá session_id={$session->session_id}, trước: " . json_encode($old) . ", sau: " . json_encode($session->toArray()),
            'user_action'
        );
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

    public function destroy(Request $request, $sessionId)
    {
       $session = AuctionSession::findOrFail($sessionId);

        $this->logActivity(
            auth::user()->user_id,
            'delete',
            'auction_session',
            $session->session_id,
            "{$request->user()->full_name} Xóa phiên đấu giá session_id={$session->session_id}, item_id={$session->item_id}",
            'user_action'
        );

        $session->delete();
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
        $this->logActivity(
            $authUser->user_id,
            'update',
            'auction_profile',
            $profile->id,
            "Người dùng user_id={$profile->user_id} bị kick khỏi phiên session_id={$session->session_id}, lý do: {$reason}",
            'admin_action'
        );

        event(new \App\Events\AuctionSessionUpdated($session));

        return response()->json([
            'status'  => $updated,
            'message' => $updated
                ? 'Đã kích người dùng ra khỏi phiên đấu giá'
                : 'Cập nhật trạng thái bị kick thất bại',
            'profile' => $profile->fresh(),
        ]);
    }

    public function confirmWinner($id)
    {
        try {
            $session = AuctionSession::with(['item', 'bids', 'profiles.user'])->findOrFail($id);

            // 🔒 Kiểm tra phiên đã kết thúc chưa
            if ($session->status !== 'KetThuc') {
                return response()->json([
                    'status' => false,
                    'message' => 'Phiên đấu giá chưa kết thúc.'
                ], 400);
            }

            // ⚠️ Kiểm tra nếu đã xác nhận rồi
            if (!is_null($session->confirm_winner_at)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Phiên này đã xác nhận người thắng rồi.'
                ], 400);
            }

            // ❗ Kiểm tra nếu đã bị từ chối trước đó
            if (!is_null($session->reject_winner_at)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Phiên này đã bị từ chối kết quả, không thể xác nhận.'
                ], 400);
            }

            // 🧍‍♂️ Lấy người thắng hiện tại
            $winnerId = $session->current_winner_id;
            if (!$winnerId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không tìm thấy người thắng.'
                ], 404);
            }

            $winner = User::find($winnerId);
            if (!$winner) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không tìm thấy thông tin người thắng.'
                ], 404);
            }

            // 🕒 Cập nhật thời gian xác nhận
            $session->confirm_winner_at = Carbon::now();
            // $session->reject_winner_at = null;
            $session->save();

            // 📧 Gửi mail thông báo nếu có email
            if (!empty($winner->email)) {
                    Mail::to($winner->email)->send(new WinnerNotification($session, $winner));
            }

            // ✅ Trả về kết quả
            return response()->json([
                'status' => true,
                'message' => 'Xác nhận người thắng thành công.',
                'data' => [
                    'winner_id' => $winner->user_id,
                    'winner_name' => $winner->full_name,
                    'winner_email' => $winner->email,
                    'confirm_winner_at' => $session->confirm_winner_at,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Đã xảy ra lỗi khi xác nhận người thắng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rejectWinner(Request $request, $id)
    {
        try {
            $session = AuctionSession::findOrFail($id);

            // Kiểm tra xem phiên có thể từ chối không
            if ($session->status !== 'KetThuc') {
                return response()->json([
                    'status' => false,
                    'message' => 'Phiên đấu giá chưa kết thúc.'
                ], 400);
            }

            // Nếu đã xác nhận người thắng rồi thì không được từ chối nữa
            if ($session->confirm_winner_at !== null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Phiên này đã xác nhận người thắng, không thể từ chối.'
                ], 400);
            }

            // Nếu đã từng từ chối rồi
            if ($session->reject_winner_at !== null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Phiên này đã bị từ chối trước đó.'
                ], 400);
            }

            // Ghi nhận lý do từ chối
            $reason = $request->input('reason');
            if (empty($reason)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng nhập lý do từ chối.'
                ], 422);
            }

            $session->reject_winner_at = Carbon::now();
            $session->rejected_reason = $reason;
            $session->save();

            return response()->json([
                'status' => true,
                'message' => 'Đã từ chối kết quả đấu giá.',
                'reject_winner_at' => $session->reject_winner_at,
                'rejected_reason' => $session->rejected_reason
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Đã xảy ra lỗi khi từ chối kết quả.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
