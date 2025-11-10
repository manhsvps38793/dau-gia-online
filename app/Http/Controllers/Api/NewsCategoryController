<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use Illuminate\Http\Request;

class NewsCategoryController extends Controller
{
    /**
     * Lấy danh sách tất cả danh mục tin tức
     * GET /api/news-categories
     */
    public function index()
    {
        $categories = NewsCategory::orderBy('created_at', 'desc')->get();
        return response()->json($categories);
    }

    /**
     * Tạo danh mục tin tức mới
     * POST /api/news-categories
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string', // ✅ thêm validate mô tả
        ]);

        $category = NewsCategory::create($validated);

        return response()->json([
            'message' => 'Thêm danh mục thành công',
            'data' => $category,
        ], 201);
    }

    /**
     * Xem chi tiết 1 danh mục tin tức
     * GET /api/news-categories/{id}
     */
    public function show($id)
    {
        $category = NewsCategory::findOrFail($id);
        return response()->json($category);
    }

    /**
     * Cập nhật danh mục tin tức
     * PUT /api/news-categories/{id}
     */
    public function update(Request $request, $id)
    {
        $category = NewsCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string', // ✅ thêm validate mô tả
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Cập nhật danh mục thành công',
            'data' => $category,
        ]);
    }

    /**
     * Xóa danh mục tin tức
     * DELETE /api/news-categories/{id}
     */
    public function destroy($id)
    {
        $category = NewsCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Danh mục đã được xóa thành công',
        ]);
    }
}
