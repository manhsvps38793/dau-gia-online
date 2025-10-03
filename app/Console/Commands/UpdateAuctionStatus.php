<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuctionSession;
use App\Events\AuctionSessionUpdated;
use Carbon\Carbon;

class UpdateAuctionStatus extends Command
{
    protected $signature = 'auction:update-status';
    protected $description = 'Tự động cập nhật trạng thái phiên đấu giá theo thời gian thực tế';

    public function handle()
    {
        $now = Carbon::now(config('app.timezone'));

        // Mo -> DangDienRa
        $starting = AuctionSession::where('status', 'Mo')
            ->where('bid_start', '<=', $now)
            ->get();

        foreach ($starting as $session) {
            $session->status = 'DangDienRa';
            $session->save();
            broadcast(new AuctionSessionUpdated($session))->toOthers();
        }

        // DangDienRa -> KetThuc
        $ending = AuctionSession::where('status', 'DangDienRa')
            ->where('bid_end', '<=', $now)
            ->get();

        foreach ($ending as $session) {
            if ($session->status !== 'KetThuc') {
                $session->status = 'KetThuc';
                $session->save();
                broadcast(new AuctionSessionUpdated($session))->toOthers();
            }
        }

        $this->info('Cập nhật trạng thái phiên đấu giá xong!');
    }
}
