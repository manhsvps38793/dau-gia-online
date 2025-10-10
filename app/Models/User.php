<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'Users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password',
        'role',
        'created_at',
        'deleted_at',
        'address',
        'id_card_front',
        'id_card_back',
        'bank_name',
        'bank_account',
        'verify_token',
        'email_verified_at'
    ];

    protected $hidden = ['password'];

    public function auctionProfiles()
    {
        return $this->hasMany(AuctionProfile::class, 'user_id', 'user_id');
    }

    // ✅ Chuyển múi giờ sang Asia/Ho_Chi_Minh khi serialize
    public function serializeDate(\DateTimeInterface $date)
    {
       $carbonDate = Carbon::instance($date)->setTimezone('Asia/Ho_Chi_Minh');
        return $carbonDate->format('Y-m-d\TH:i:sP');
    }
}
