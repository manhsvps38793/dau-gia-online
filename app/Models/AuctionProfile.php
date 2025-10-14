<?php
namespace App\Models;

class AuctionProfile extends BaseModel
{
    protected $table = 'AuctionProfiles';
    protected $primaryKey = 'profile_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'session_id',
        'document_url',
        'deposit_amount',
        'status',
        'created_at',
        'is_kicked',    // ✅ thêm
        'kick_reason',  // ✅ thêm
    ];

    protected $casts = [
        'is_kicked' => 'boolean', // ✅ để tự động cast 0/1 thành false/true
    ];

    // Quan hệ tới người dùng
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function item() {
        return $this->belongsTo(AuctionItem::class, 'item_id', 'item_id');
    }

    public function session() {
        return $this->belongsTo(AuctionSession::class, 'session_id', 'session_id');
    }

    public function depositPayment() {
        return $this->hasOne(DepositPayment::class, 'profile_id', 'profile_id');
    }
}
