<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends BaseModel
{
    protected $table = 'Payments';
    protected $primaryKey = 'payment_id';
    public $timestamps = false;

    protected $fillable = [
        'contract_id', 'sender_id', 'amount', 'payment_date', 'method', 'status', 'receiver_id', 'profile_id'
    ];

    public function contract() {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
