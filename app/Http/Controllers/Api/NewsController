<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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




public function store(Request $request)
{
    $data = $request->validate([
        'category_id' => 'required|exists:news_categories,id',
        'title' => 'required|string|max:255',
        'content' => 'required',
        'author' => 'nullable|string|max:255',
        'is_published' => 'boolean',
        'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($request->hasFile('thumbnail')) {
        $file = $request->file('thumbnail');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('news', $filename, 'public');

        // Tạo URL đầy đủ
        $data['thumbnail'] = asset('storage/news/' . $filename);
        // Kết quả: http://localhost:8000/storage/news/xxxx.jpg
    }

    $news = News::create($data);

    return response()->json([
        'message' => 'Thêm tin tức thành công!',
        'data' => $news
    ], 201);
}




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
            'content' => 'sometimes',
            'author' => 'nullable|string|max:255',
            'is_published' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // 📸 Upload ảnh mới (và xóa ảnh cũ)
        if ($request->hasFile('thumbnail')) {
            // Xóa ảnh cũ (nếu có)
            if ($news->thumbnail) {
                $oldPath = str_replace('/storage/', 'public/', $news->thumbnail);
                if (Storage::exists($oldPath)) {
                    Storage::delete($oldPath);
                }
            }

            // Upload ảnh mới vào thư mục public/news
            $file = $request->file('thumbnail');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('news', $filename, 'public'); // đúng chuẩn

            // Lưu URL public của ảnh
            $data['thumbnail'] = Storage::url('news/' . $filename);
        }

        $news->update($data);

        return response()->json([
            'message' => 'Cập nhật tin tức thành công!',
            'data' => $news->load('category')
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

        // Xóa ảnh nếu có
        if ($news->thumbnail && Storage::exists('public/news/' . $news->thumbnail)) {
            Storage::delete('public/news/' . $news->thumbnail);
        }

        $news->delete();

        return response()->json(['message' => 'Đã xóa tin tức thành công!']);
    }
}
