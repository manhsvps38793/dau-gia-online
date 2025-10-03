<?php
namespace App\Events;

use App\Models\AuctionItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ItemUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $item;

    public function __construct(AuctionItem $item)
    {
        $this->item = $item;
    }

    public function broadcastOn()
    {
        return new Channel('auction-item.' . $this->item->item_id);
    }

    public function broadcastAs()
    {
        return 'auction.item.updated';
    }
}
