<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AuctionItem;
use App\Http\Resources\AuctionItemResource;

class AuctionItemController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'category_id' => 'required|exists:Categories,category_id',
        'owner_id'    => 'required|exists:Users,user_id',
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
        'starting_price' => 'required|numeric|min:1',
        'image_url'   => 'nullable|string',
        'status'      => 'in:ChoDauGia,DangDauGia,DaBan,Huy'
    ]);

    $item = AuctionItem::create([
        'category_id' => $request->category_id,
        'owner_id'    => $request->owner_id,
        'name'        => $request->name,
        'description' => $request->description,
        'starting_price' => $request->starting_price,
        'image_url'   => $request->image_url,
        'status'      => $request->status ?? 'ChoDauGia',
        'created_at'  => now()
    ]);

    return response()->json([
        'status' => true,
        'message'=> 'Tạo sản phẩm thành công',
        'item'   => $item
    ]);
}

    // GET /api/auction-items
    public function index()
    {
        $items = AuctionItem::with('category')
            ->whereIn('status', ['ChoDauGia', 'DangDauGia'])
            ->get();

        return AuctionItemResource::collection($items);
    }

    // GET /api/auction-items/{id}
    public function show($id)
    {
        $item = AuctionItem::with('category')->findOrFail($id);

        return new AuctionItemResource($item);
    }
}
