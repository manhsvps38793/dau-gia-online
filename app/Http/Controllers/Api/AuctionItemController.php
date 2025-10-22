<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuctionItem;
use App\Models\ItemImage;
use App\Http\Resources\AuctionItemResource;
use App\Http\Resources\ItemImageResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
class AuctionItemController extends Controller
{
    // Danh sách sản phẩm (chưa xóa)
   public function index(Request $request)
    {
        // Tạo query cơ bản, kèm category và owner
        $query = AuctionItem::with(['category', 'owner'])
            ->whereNull('deleted_at');

        // 🔍 Nếu có truyền owner_id -> lọc theo chủ sở hữu
        if ($request->has('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        // 🕒 Sắp xếp mới nhất
        $items = $query->orderByDesc('created_at')->get();

        // Trả về dữ liệu resource chuẩn
        return AuctionItemResource::collection($items);
    }

    // Tạo mới
    public function store(Request $request)
    {
        $request->validate([
            'category_id'    => 'required|exists:Categories,category_id',
            'owner_id'       => 'required|exists:Users,user_id',
            'auction_org_id' => 'nullable|integer',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'starting_price' => 'required|numeric|min:1',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'url_file'       => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:5120',
            'extra_images.*' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'status'         => ['nullable', Rule::in(['ChoDuyet','ChoDauGia','DangDauGia','DaBan','Huy'])],
        ]);

        DB::beginTransaction();
        try {
            // Image chính
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('auction_images', 'public');
                $imageUrl = Storage::url($path);
            }

            // File tài liệu
            $fileUrl = null;
            if ($request->hasFile('url_file')) {
                $path = $request->file('url_file')->store('auction_files', 'public');
                $fileUrl = Storage::url($path);
            }

            // Tạo item
            $item = AuctionItem::create([
                'category_id'    => $request->category_id,
                'owner_id'       => $request->owner_id,
                'auction_org_id' => $request->auction_org_id,
                'name'           => $request->name,
                'description'    => $request->description,
                'starting_price' => $request->starting_price,
                'image_url'      => $imageUrl,
                'url_file'       => $fileUrl,
                'status'         => $request->status ?? 'ChoDuyet',
            ]);

            // Thêm ảnh phụ nếu có
            if ($request->hasFile('extra_images')) {
                foreach ($request->file('extra_images') as $file) {
                    $p = $file->store('auction_images', 'public');
                    ItemImage::create([
                        'item_id' => $item->item_id,
                        'image_url' => Storage::url($p),
                        'is_primary' => false,
                    ]);
                }
            }

            DB::commit();

            broadcast(new \App\Events\ItemCreated($item))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'Tạo sản phẩm thành công',
                'item' => new AuctionItemResource($item->load('images','category','owner'))
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Lỗi khi tạo sản phẩm', 'error' => $e->getMessage()], 500);
        }
    }


    public function show($id)
    {
        $item = AuctionItem::with(['category','owner','sessions.auctionOrg','sessions.bids.user','sessions.contract','images'])
            ->findOrFail($id);

        if ($item->deleted_at) {
            return response()->json(['status' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        return new AuctionItemResource($item);
    }

    // Cập nhật
     public function update(Request $request, $id)
    {
        $item = AuctionItem::findOrFail($id);

        if ($item->deleted_at) {
            return response()->json(['status' => false, 'message' => 'Sản phẩm đã bị xóa'], 404);
        }

        $validated = $request->validate([
            'category_id'    => 'sometimes|exists:Categories,category_id',
            'auction_org_id' => 'nullable|integer',
            'name'           => 'sometimes|string|max:255',
            'description'    => 'nullable|string',
            'starting_price' => 'sometimes|numeric|min:1',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'url_file'       => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:5120',
            'extra_images.*' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'status'         => ['sometimes', Rule::in(['ChoDuyet','ChoDauGia','DangDauGia','DaBan','Huy'])],
        ]);

        DB::beginTransaction();
        try {
            // Image chính
            if ($request->hasFile('image')) {
                if ($item->image_url && str_contains($item->image_url, '/storage/')) {
                    $oldPath = ltrim(str_replace('/storage/', '', $item->image_url), '/');
                    Storage::disk('public')->delete($oldPath);
                }
                $p = $request->file('image')->store('auction_images', 'public');
                $validated['image_url'] = Storage::url($p);
            }

            // File tài liệu
            if ($request->hasFile('url_file')) {
                if ($item->url_file && str_contains($item->url_file, '/storage/')) {
                    $oldFile = ltrim(str_replace('/storage/', '', $item->url_file), '/');
                    Storage::disk('public')->delete($oldFile);
                }
                $p = $request->file('url_file')->store('auction_files', 'public');
                $validated['url_file'] = Storage::url($p);
            }

            $item->fill($validated);
            $item->save();

            // Ảnh phụ
            if ($request->hasFile('extra_images')) {
                foreach ($request->file('extra_images') as $file) {
                    $path = $file->store('auction_images', 'public');
                    ItemImage::create([
                        'item_id' => $item->item_id,
                        'image_url' => Storage::url($path),
                        'is_primary' => false,
                    ]);
                }
            }

            DB::commit();

            broadcast(new \App\Events\ItemUpdated($item))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật sản phẩm thành công',
                'item' => new AuctionItemResource($item->load('images','category','owner'))
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Lỗi khi cập nhật sản phẩm', 'error' => $e->getMessage()], 500);
        }
    }

    // Xóa mềm sản phẩm
    public function destroy(Request $request, $id)
    {
        $item = AuctionItem::findOrFail($id);

        if ($item->deleted_at) {
            return response()->json(['status' => false, 'message' => 'Sản phẩm đã bị xóa'], 404);
        }


        // đặt deleted_at, đổi trạng thái
        $item->deleted_at = now();
        $item->status = 'Huy';
        $item->save();

        return response()->json(['status' => true, 'message' => 'Xóa sản phẩm thành công']);
    }

    // Xóa 1 ảnh phụ theo id
    public function removeImage(Request $request, $imageId)
    {
        $img = ItemImage::findOrFail($imageId);
        // xóa file vật lý nếu lưu trong storage
        if ($img->image_url && str_contains($img->image_url, '/storage/')) {
            $oldPath = ltrim(str_replace('/storage/', '', $img->image_url), '/');
            Storage::disk('public')->delete($oldPath);
        }
        $img->delete();
        return response()->json(['status' => true, 'message' => 'Xóa ảnh phụ thành công']);
    }

    // Đặt 1 ảnh phụ là ảnh chính (hoặc hủy)
    public function setPrimaryImage(Request $request, $itemId, $imageId)
    {
        $item = AuctionItem::findOrFail($itemId);
        $img = ItemImage::where('item_id', $itemId)->where('image_id', $imageId)->firstOrFail();

        // bắt đầu transaction: đặt tất cả is_primary false, set true cho ảnh chọn
        DB::beginTransaction();
        try {
            ItemImage::where('item_id', $itemId)->update(['is_primary' => false]);
            $img->is_primary = true;
            $img->save();

            // cập nhật image_url của item để trùng với ảnh chính
            $item->image_url = $img->image_url;
            $item->save();

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Đặt ảnh chính thành công', 'image' => new ItemImageResource($img)]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Lỗi khi đặt ảnh chính'], 500);
        }
    }

    // Lấy danh sách ảnh phụ của 1 item
    public function images($itemId)
    {
        $images = ItemImage::where('item_id', $itemId)->orderByDesc('is_primary')->get();
        return ItemImageResource::collection($images);
    }

    // 🔍 Tìm kiếm sản phẩm đấu giá
    public function search(Request $request)
    {
        $keyword = $request->input('q'); // q là từ khóa tìm kiếm
        $status  = $request->input('status'); // lọc theo trạng thái (tùy chọn)
        $categoryId = $request->input('category_id'); // lọc theo danh mục (tùy chọn)

        $query = AuctionItem::with(['category', 'owner'])
            ->whereNull('deleted_at');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%")
                ->orWhereHas('category', function ($sub) use ($keyword) {
                    $sub->where('category_name', 'like', "%{$keyword}%");
                })
                ->orWhereHas('owner', function ($sub) use ($keyword) {
                    $sub->where('username', 'like', "%{$keyword}%");
                });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderByDesc('created_at')->paginate(10);

        return AuctionItemResource::collection($items);
    }

}
