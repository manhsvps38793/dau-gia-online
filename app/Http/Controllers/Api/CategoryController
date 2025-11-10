<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    // 📌 Lấy danh sách tất cả danh mục
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'status' => true,
            'message' => 'Danh sách danh mục',
            'data' => $categories
        ], 200);
    }

    // 📌 Lấy chi tiết 1 danh mục
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $category
        ], 200);
    }

    // 📌 Thêm danh mục mới
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create([
            'name'        => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Thêm danh mục thành công',
            'data' => $category
        ], 201);
    }

    // 📌 Cập nhật danh mục
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
           'name' => 'required|string|max:255|unique:categories,name,' . $category->category_id . ',category_id',
           'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update([
            'name'        => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật danh mục thành công',
            'data' => $category
        ], 200);
    }

    // 📌 Xóa danh mục
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        }

        // Kiểm tra xem danh mục có chứa tài sản (AuctionItem) không
        if ($category->auctionItems()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể xóa danh mục vì danh mục này chứa tài sản'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa danh mục thành công'
        ], 200);
    }
}
