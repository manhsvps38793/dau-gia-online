<?php

namespace App\Jobs;

use App\Models\AuctionSession;
use App\Events\AuctionSessionUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Bid;
use App\Models\Contract;
use App\Models\Notification;
class EndAuctionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sessionId;

    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function handle()
{
    $session = AuctionSession::find($this->sessionId);
    if (!$session) return;

    if ($session->status !== 'KetThuc' && now()->gte($session->bid_end)) {

        // Lấy giá cao nhất
        $highestBid = Bid::where('session_id', $session->session_id)
                         ->orderBy('amount', 'desc')
                         ->first();

        if ($highestBid) {
            // Tạo Contract cho người thắng
            Contract::create([
                'session_id' => $session->session_id,
                'winner_id' => $highestBid->user_id,
                'final_price' => $highestBid->amount,
                'signed_date' => now(),
                'status' => 'ChoThanhToan',
            ]);

            // Tạo Notification cho người thắng
            Notification::create([
                'user_id' => $highestBid->user_id,
                'message' => "Chúc mừng! Bạn thắng phiên đấu giá #{$session->session_id} với giá {$highestBid->amount} VND.",
                'sent_at' => now()
            ]);
        }

        $session->status = 'KetThuc';
        $session->save();
        broadcast(new AuctionSessionUpdated($session))->toOthers();
    }
}
}
