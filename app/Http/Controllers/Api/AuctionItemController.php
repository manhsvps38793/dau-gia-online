<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuctionItem;
use App\Http\Resources\AuctionItemResource;
use Illuminate\Support\Facades\Storage;

class AuctionItemController extends Controller
{
    // 🟢 CREATE (Thêm sản phẩm)
    public function store(Request $request)
    {
        $request->validate([
            'category_id'    => 'required|exists:Categories,category_id',
            'owner_id'       => 'required|exists:Users,user_id',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'starting_price' => 'required|numeric|min:1',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'status'         => 'in:ChoDuyet,ChoDauGia,DangDauGia,DaBan,Huy'
        ]);

        // ✅ Lưu file hình ảnh nếu có
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('auction_images', 'public');
            $imageUrl = Storage::url($path);
        }

        // ✅ Tạo sản phẩm
        $item = AuctionItem::create([
            'category_id'    => $request->category_id,
            'owner_id'       => $request->owner_id,
            'name'           => $request->name,
            'description'    => $request->description,
            'starting_price' => $request->starting_price,
            'image_url'      => $imageUrl,
            'status'         => $request->status ?? 'ChoDuyet',
            'created_at'     => now(),
        ]);

        // 🟢 Realtime broadcast
        broadcast(new \App\Events\ItemCreated($item))->toOthers();

        return response()->json([
            'status'  => true,
            'message' => 'Tạo sản phẩm thành công',
            'item'    => new AuctionItemResource($item),
        ], 201);
    }

    // 🟡 LIST (Danh sách)
    public function index()
    {
        $items = AuctionItem::with('category')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        return AuctionItemResource::collection($items);
    }

    // 🟣 SHOW (Chi tiết sản phẩm)
    public function show($id)
    {
        $item = AuctionItem::with([
            'category',
            'owner',
            'sessions.auctionOrg',
            'sessions.bids.user',
            'sessions.contract'
        ])->findOrFail($id);

        if ($item->deleted_at) {
            return response()->json(['status' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        return new AuctionItemResource($item);
    }

    // 🟠 UPDATE (Cập nhật)
    public function update(Request $request, $id)
    {
        $item = AuctionItem::findOrFail($id);

        if ($item->deleted_at) {
            return response()->json(['status' => false, 'message' => 'Sản phẩm đã bị xóa'], 404);
        }

        // ✅ Kiểm tra quyền
        $user = $request->user();
        $allowedRoles = ['Administrator', 'ToChucDauGia'];
        if ($item->owner_id !== $user->user_id && !in_array($user->role, $allowedRoles)) {
            return response()->json(['status' => false, 'message' => 'Không có quyền sửa sản phẩm này'], 403);
        }

        // ✅ Validate dữ liệu cập nhật
        $validated = $request->validate([
            'category_id'    => 'sometimes|exists:Categories,category_id',
            'name'           => 'sometimes|string|max:255',
            'description'    => 'nullable|string',
            'starting_price' => 'sometimes|numeric|min:1',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'status'         => 'sometimes|in:ChoDuyet,ChoDauGia,DangDauGia,DaBan,Huy'
        ]);

        // ✅ Upload ảnh mới nếu có
        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu có
            if ($item->image_url && str_contains($item->image_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $item->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('auction_images', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        // ✅ Cập nhật thông tin
        $item->fill($validated);
        $item->save();

        // 🔔 Broadcast realtime
        broadcast(new \App\Events\ItemUpdated($item))->toOthers();

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật sản phẩm thành công',
            'item'    => new AuctionItemResource($item)
        ]);
    }

    // 🔴 DESTROY (Xóa mềm)
    public function destroy(Request $request, $id)
    {
        $item = AuctionItem::findOrFail($id);

        if ($item->deleted_at) {
            return response()->json(['status' => false, 'message' => 'Sản phẩm đã bị xóa'], 404);
        }

        $user = $request->user();
        $allowedRoles = ['Administrator', 'ToChucDauGia'];
        if ($item->owner_id !== $user->user_id && !in_array($user->role, $allowedRoles)) {
            return response()->json(['status' => false, 'message' => 'Không có quyền xóa sản phẩm này'], 403);
        }

        $item->deleted_at = now();
        $item->status = 'Huy';
        $item->save();

        return response()->json([
            'status'  => true,
            'message' => 'Xóa sản phẩm thành công'
        ]);
    }
}
