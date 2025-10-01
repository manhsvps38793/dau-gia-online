<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens; // Thêm để tạo token
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable; // thêm trait HasApiTokens

    protected $table = 'Users';
    protected $primaryKey = 'user_id';
    public $timestamps = false; // bạn đã có created_at, deleted_at


    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password',
        'role',
        'created_at',
        'deleted_at'
    ];

    protected $hidden = ['password'];

    // Nếu muốn tự hash password khi tạo hoặc cập nhật
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
}
