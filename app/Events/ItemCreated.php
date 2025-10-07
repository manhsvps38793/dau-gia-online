<?php
namespace App\Events;

use App\Models\AuctionItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ItemCreated implements ShouldBroadcast
{
    use SerializesModels;

    public $item;

    public function __construct(AuctionItem $item)
    {
        $this->item = $item;
    }

    public function broadcastOn()
    {
        // Phát đến kênh tổng cho danh sách sản phẩm
        return new Channel('auction-items');
    }

    public function broadcastAs()
    {
        return 'auction.item.created';
    }
}
