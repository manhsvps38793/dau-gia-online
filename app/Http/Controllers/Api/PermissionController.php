<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return response()->json(['status'=>true,'permissions'=>Permission::all()]);
    }

    public function store(Request $request)
    {
        $request->validate(['name'=>'required|string|unique:permissions,name']);
        $permission = Permission::create($request->only('name','description'));

        return response()->json(['status'=>true,'permission'=>$permission],201);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        $request->validate(['name'=>'required|string|unique:permissions,name,'.$permission->id]);
        $permission->update($request->only('name','description'));

        return response()->json(['status'=>true,'permission'=>$permission]);
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json(['status'=>true,'message'=>'Permission đã được xóa']);
    }
}
