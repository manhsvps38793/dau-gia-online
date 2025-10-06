<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionProfile extends Model
{
    protected $table = 'AuctionProfiles';
    protected $primaryKey = 'profile_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'session_id', 'document_url', 'deposit_amount', 'status', 'created_at'
    ];

    // Quan hệ tới người dùng
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Quan hệ tới tài sản (nếu cần)
    public function item() {
        return $this->belongsTo(AuctionItem::class, 'item_id', 'item_id');
    }

    // 🔹 Chỉnh sửa: quan hệ tới phiên đấu giá bằng session_id
    public function session() {
        return $this->belongsTo(AuctionSession::class, 'session_id', 'session_id');
    }
}
