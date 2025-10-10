<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bid extends BaseModel
{
    protected $table = 'Bids';
    protected $primaryKey = 'bid_id';
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'user_id',
        'amount',
        'bid_time'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function session()
    {
        return $this->belongsTo(AuctionSession::class, 'session_id', 'session_id');
    }
}
