<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionItem;
use App\Http\Resources\AuctionItemResource;

class AuctionItemController extends Controller
{
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
        $item = AuctionItem::with([
            'category',
            'owner',
            'sessions.auctionOrg',
            'sessions.bids.user',
            'sessions.contract'
        ])->findOrFail($id);

        return new AuctionItemResource($item);
    }
}
