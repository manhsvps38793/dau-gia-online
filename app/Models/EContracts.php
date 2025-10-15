<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EContracts extends BaseModel
{
    use HasFactory;
    protected $table = 'econtracts';
    protected $primaryKey = 'econtract_id';
 public $timestamps = false;
    protected $fillable = [
        'contract_type',
        'file_url',
        'signed_by',
        'signed_at',
        'session_id',
        'contract_id'
    ];

    public function contract()
    {
        return $this->belongsTo(contract::class, 'contract_id', 'contract_id');
    }

    public function session()
    {
        return $this->belongsTo(AuctionSession::class, 'session_id', 'session_id');
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signed_by', 'user_id');
    }
}
