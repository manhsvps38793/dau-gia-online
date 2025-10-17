<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class UserRoleController extends Controller
{
    // Xem role user
    public function index($userId)
    {
        $user = User::with('roles.permissions')->findOrFail($userId);
        return response()->json(['status'=>true,'roles'=>$user->roles]);
    }

    // Gán role cho user
    public function assignRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        $roles = Role::whereIn('name', $request->roles)->pluck('id')->toArray();
        $user->roles()->syncWithoutDetaching($roles);

        return response()->json(['status'=>true,'user'=>$user->load('roles')]);
    }

    // Xóa role của user
    public function removeRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        $roles = Role::whereIn('name', $request->roles)->pluck('id')->toArray();
        $user->roles()->detach($roles);

        return response()->json(['status'=>true,'user'=>$user->load('roles')]);
    }
}
