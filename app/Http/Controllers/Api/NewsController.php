<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * 📄 Lấy danh sách tin tức
     */
    public function index()
    {
        $news = News::with('category')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($news);
    }

    /**
     * ➕ Thêm tin tức mới
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:news_categories,id',
            'title' => 'required|string|max:255',
            'thumbnail' => 'nullable|string',
            'content' => 'required',
            'author' => 'nullable|string|max:255',
            'is_published' => 'boolean',
        ]);

        $news = News::create($data);
        return response()->json([
            'message' => 'Thêm tin tức thành công!',
            'data' => $news
        ], 201);
    }

    /**
     * 👀 Xem chi tiết tin tức theo ID
     */
    public function show($id)
    {
        $news = News::with('category')->find($id);

        if (!$news) {
            return response()->json(['message' => 'Không tìm thấy tin tức'], 404);
        }

        return response()->json($news);
    }

    /**
     * ✏️ Cập nhật tin tức
     */
    public function update(Request $request, $id)
    {
        $news = News::find($id);
        if (!$news) {
            return response()->json(['message' => 'Không tìm thấy tin tức'], 404);
        }

        $data = $request->validate([
            'category_id' => 'sometimes|exists:news_categories,id',
            'title' => 'sometimes|string|max:255',
            'thumbnail' => 'nullable|string',
            'content' => 'sometimes',
            'author' => 'nullable|string|max:255',
            'is_published' => 'boolean',
        ]);

        $news->update($data);

        return response()->json([
            'message' => 'Cập nhật tin tức thành công!',
            'data' => $news
        ]);
    }

    /**
     * 🗑️ Xóa tin tức
     */
    public function destroy($id)
    {
        $news = News::find($id);
        if (!$news) {
            return response()->json(['message' => 'Không tìm thấy tin tức'], 404);
        }

        $news->delete();

        return response()->json(['message' => 'Đã xóa tin tức thành công!']);
    }
}
