<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends BaseModel
{
    protected $table = 'UserPermissions';
    public $timestamps = false;
    protected $fillable = ['user_id', 'permission_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'permission_id');
    }
}
