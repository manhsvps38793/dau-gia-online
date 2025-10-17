<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends BaseModel
{
    protected $table = 'Permissions';
    protected $primaryKey = 'permission_id';
    public $timestamps = false;

    protected $fillable = ['name', 'description'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'RolePermissions', 'permission_id', 'role_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'UserPermissions', 'permission_id', 'user_id');
    }
}
