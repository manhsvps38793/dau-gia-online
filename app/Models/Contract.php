<?php
namespace App\Models;


class Contract extends BaseModel
{
     protected $table = 'contracts';
    protected $primaryKey = 'contract_id';

protected $fillable = [
    'session_id',
    'winner_id',
    'final_price',
    'signed_date',
    'status',
    'file_path', // thêm dòng này
];
protected $casts = [
    'winner_id' => 'integer',
    'final_price' => 'decimal:2',
    'signed_date' => 'datetime',
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
