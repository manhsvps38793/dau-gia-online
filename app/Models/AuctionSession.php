<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionSession extends Model
{

    protected $table = 'AuctionSessions';
    protected $primaryKey = 'session_id';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'created_by',
        'auction_org_id',
        'start_time',
        'end_time',
        'regulation',
        'method',
        'register_start',
        'register_end',
        'checkin_time',
        'bid_start',
        'bid_end',
        'bid_step',
        'status',
    ];

    public function item()
    {
        return $this->belongsTo(AuctionItem::class, 'item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function auctionOrg()
    {
        return $this->belongsTo(User::class, 'auction_org_id', 'user_id');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class, 'session_id');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class, 'session_id');
    }
}
