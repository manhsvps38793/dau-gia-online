<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionProfile extends Model
{
    protected $table = 'AuctionProfiles';
    protected $primaryKey = 'profile_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'item_id', 'document_url', 'deposit_amount', 'status', 'created_at'
    ];

   public function user() {
    return $this->belongsTo(User::class, 'user_id', 'user_id');
}

public function item() {
    return $this->belongsTo(AuctionItem::class, 'item_id', 'item_id');
}
    public function session() {
        return $this->belongsTo(AuctionSession::class, 'item_id', 'item_id');
}
}


