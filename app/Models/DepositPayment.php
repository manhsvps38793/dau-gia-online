<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositPayment extends BaseModel
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'depositpayments';
    protected $primaryKey = 'deposit_id';

    protected $fillable = [
        'profile_id',
        'amount',
        'payment_method',
        'payment_date',
        'status',
        'receiver_id',
        'refund_status',
        'refund_date',
        'session_id'
    ];

    public function profile()
    {
        return $this->belongsTo(AuctionProfile::class, 'profile_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
