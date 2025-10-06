<?php

namespace App\Events;

use App\Models\AuctionProfile;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class AuctionProfileUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $profile;

    public function __construct(AuctionProfile $profile)
    {
        $this->profile = $profile;
    }

    // 🔹 Kênh broadcast
    public function broadcastOn()
    {
        return new Channel('auction-profiles'); // công khai
    }

    // 🔹 Tên sự kiện gửi tới FE
    public function broadcastAs()
    {
        return 'profile.updated';
    }
}
