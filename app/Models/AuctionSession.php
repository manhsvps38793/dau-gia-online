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
        'start_time',
        'end_time',
        'regulation',
        'status'
    ];

    public function item() {
        return $this->belongsTo(AuctionItem::class, 'item_id' , 'item_id');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
