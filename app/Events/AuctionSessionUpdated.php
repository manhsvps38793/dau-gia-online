<?php

namespace App\Events;

use App\Models\AuctionSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class AuctionSessionUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $session;

    public function __construct(AuctionSession $session)
    {
        $this->session = $session;
    }

    public function broadcastOn()
    {
        return new Channel('auction-session.' . $this->session->session_id);
    }

    public function broadcastAs()
    {
        return 'auction.session.updated';
    }
}
