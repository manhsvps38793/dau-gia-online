<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionSessionFavorite extends Model
{
    protected $table = 'auction_session_favorites';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = ['user_id', 'session_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function session()
    {
        return $this->belongsTo(AuctionSession::class, 'session_id', 'session_id');
    }
}
