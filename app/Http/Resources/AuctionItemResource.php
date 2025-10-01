<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuctionItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->item_id,
            'ten'            => $this->name,
            'mo_ta'          => $this->description,
            'gia_khoi_diem'  => $this->starting_price,
            'trang_thai'     => $this->status,
            'hinh_anh'       => $this->image_url,
            'loai'           => $this->category ? $this->category->name : null,
            'ngay_tao'       => $this->created_at,
        ];
    }
}
