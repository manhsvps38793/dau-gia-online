<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'role_id';
    public $timestamps = false;
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = ['name', 'description'];

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'RolePermissions',
            'role_id',
            'permission_id'
        );
    }

    // Không cần users() nếu không truy ngược
}
