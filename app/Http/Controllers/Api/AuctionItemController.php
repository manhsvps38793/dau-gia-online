<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuctionItem;
use App\Http\Resources\AuctionItemResource;

class AuctionItemController extends Controller
{
    // CREATE
    public function store(Request $request)
    {
        $request->validate([
            'category_id'    => 'required|exists:Categories,category_id',
            'owner_id'       => 'required|exists:Users,user_id',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'starting_price' => 'required|numeric|min:1',
            'image_url'      => 'nullable|string',
            'status'         => 'in:ChoDauGia,DangDauGia,DaBan,Huy'
        ]);

        $item = AuctionItem::create([
            'category_id'    => $request->category_id,
            'owner_id'       => $request->owner_id,
            'name'           => $request->name,
            'description'    => $request->description,
            'starting_price' => $request->starting_price,
            'image_url'      => $request->image_url,
            'status'         => $request->status ?? 'ChoDauGia',
            'created_at'     => now()
        ]);

        return response()->json([
            'status' => true,
            'message'=> 'Tạo sản phẩm thành công',
            'item'   => new AuctionItemResource($item)
        ], 201);
    }

    // LIST
    public function index()
    {
        $items = AuctionItem::with('category')
            ->whereIn('status', ['ChoDauGia', 'DangDauGia'])
            ->whereNull('deleted_at')
            ->get();

        return AuctionItemResource::collection($items);
    }

    // SHOW
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
            return response()->json(['status'=>false,'message'=>'Sản phẩm không tồn tại'],404);
        }

        return new AuctionItemResource($item);
    }

    // UPDATE (sửa)
    public function update(Request $request, $id)
    {
        $item = AuctionItem::findOrFail($id);

        if ($item->deleted_at) {
            return response()->json(['status'=>false,'message'=>'Sản phẩm đã bị xóa'], 404);
        }

        // Authorization: chỉ owner hoặc Admin/ToChucDauGia được sửa
        $user = $request->user();
        $allowedRoles = ['Administrator', 'ToChucDauGia'];
        if ($item->owner_id !== $user->user_id && !in_array($user->role, $allowedRoles)) {
            return response()->json(['status'=>false,'message'=>'Không có quyền sửa sản phẩm này'], 403);
        }

        // validate (sử dụng sometimes cho update)
        $validated = $request->validate([
            'category_id'    => 'sometimes|exists:Categories,category_id',
            'owner_id'       => 'sometimes|exists:Users,user_id',
            'name'           => 'sometimes|string|max:255',
            'description'    => 'nullable|string',
            'starting_price' => 'sometimes|numeric|min:1',
            'image_url'      => 'nullable|string',
            'status'         => 'sometimes|in:ChoDauGia,DangDauGia,DaBan,Huy'
        ]);

        // Cập nhật các trường hợp được gửi
        $item->fill($validated);
        $item->save();

        return response()->json([
            'status' => true,
            'message'=> 'Cập nhật sản phẩm thành công',
            'item'   => new AuctionItemResource($item)
        ]);
    }

    // DESTROY (soft delete)
    public function destroy(Request $request, $id)
    {
        $item = AuctionItem::findOrFail($id);

        if ($item->deleted_at) {
            return response()->json(['status'=>false,'message'=>'Sản phẩm đã bị xóa'], 404);
        }

        // Authorization: chỉ owner hoặc Admin/ToChucDauGia được xóa
        $user = $request->user();
        $allowedRoles = ['Administrator', 'ToChucDauGia'];
        if ($item->owner_id !== $user->user_id && !in_array($user->role, $allowedRoles)) {
            return response()->json(['status'=>false,'message'=>'Không có quyền xóa sản phẩm này'], 403);
        }

        // Soft delete: đặt deleted_at và trạng thái Huy
        $item->deleted_at = now();
        $item->status = 'Huy';
        $item->save();

        return response()->json([
            'status' => true,
            'message'=> 'Xóa sản phẩm thành công'
        ], 200);
    }
}
