<?php

namespace App\Jobs;

use App\Models\AuctionSession;
use App\Events\AuctionSessionUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
            $session->status = 'KetThuc';
            $session->save();
            broadcast(new AuctionSessionUpdated($session))->toOthers();
        }
    }
}
