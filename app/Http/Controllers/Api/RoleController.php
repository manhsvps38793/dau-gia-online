<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    // Danh sách role
    public function index()
    {
        return response()->json([
            'status' => true,
            'roles' => Role::with('permissions')->get()
        ]);
    }

    // Tạo role mới
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string'
        ]);

        $role = Role::create($request->only('name','description'));

        return response()->json([
            'status' => true,
            'role' => $role
        ], 201);
    }

    // Cập nhật role
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:roles,name,'.$role->id,
            'description' => 'nullable|string'
        ]);

        $role->update($request->only('name','description'));

        return response()->json(['status'=>true,'role'=>$role]);
    }

    // Xóa role
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['status'=>true,'message'=>'Role đã được xóa']);
    }

    // Gán permission cho role
    public function assignPermission(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role->permissions()->sync($request->permissions);

        return response()->json(['status'=>true,'role'=>$role->load('permissions')]);
    }
}
