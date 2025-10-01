<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    protected $table = 'Bids';   // vì bảng bạn đặt tên Bids

    protected $primaryKey = 'bid_id';

    protected $fillable = [
        'session_id',
        'user_id',
        'amount',
        'bid_time'
    ];

    public $timestamps = false; // vì bạn dùng TIMESTAMP trong SQL

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function session()
    {
        return $this->belongsTo(AuctionSession::class, 'session_id', 'session_id');
    }
}
