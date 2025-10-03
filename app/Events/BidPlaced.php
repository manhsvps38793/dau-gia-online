<?php

namespace App\Events;

use App\Models\Bid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class BidPlaced implements ShouldBroadcast
{
    use SerializesModels;

    public $bid;

    public function __construct(Bid $bid)
    {
        $this->bid = $bid;
    }

    // Channel tên phiên đấu giá
    public function broadcastOn()
    {
        return new Channel('auction-session.' . $this->bid->session_id);
    }

    public function broadcastAs()
    {
        return 'bid.placed';
    }
}