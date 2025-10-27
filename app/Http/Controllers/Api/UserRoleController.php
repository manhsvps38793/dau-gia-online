<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserRoleController extends Controller
{
    public function index($userId)
    {
        $user = User::with('role.permissions')->findOrFail($userId);
        return response()->json([
            'status' => true,
            'role' => $user->role
        ]);
    }

    public function assignRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $request->validate([
            'role_id' => 'required|exists:roles,role_id',
        ]);

        $user->role_id = $request->role_id;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Gán vai trò thành công',
            'user' => $user->load('role'),
        ]);
    }

    public function removeRole($userId)
    {
        $user = User::findOrFail($userId);
        $user->role_id = null;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Xóa vai trò thành công',
            'user' => $user->load('role'),
        ]);
    }
}
