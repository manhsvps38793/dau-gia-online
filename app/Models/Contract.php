<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $table = 'Contracts';

    protected $primaryKey = 'contract_id';

    protected $fillable = [
        'session_id',
        'winner_id',
        'final_price',
        'signed_date',
        'status'
    ];

    public $timestamps = false;

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id', 'user_id');
    }

    public function session()
    {
        return $this->belongsTo(AuctionSession::class, 'session_id', 'session_id');
    }
}
