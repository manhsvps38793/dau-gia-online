<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends BaseModel
{
    protected $table = 'Roles';
    protected $primaryKey = 'role_id';
    public $timestamps = false;

    protected $fillable = ['name', 'description'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'RolePermissions', 'role_id', 'permission_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }
    
}
