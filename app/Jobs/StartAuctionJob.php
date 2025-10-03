<?php

namespace App\Jobs;

use App\Models\AuctionSession;
use App\Events\AuctionSessionUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class StartAuctionJob implements ShouldQueue
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

        $now = Carbon::now(config('app.timezone'));

        if ($session->status === 'Mo' && $now->gte($session->bid_start)) {
            $session->status = 'DangDienRa';
            $session->save();
            broadcast(new AuctionSessionUpdated($session))->toOthers();
        }
    }
}
