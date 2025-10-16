<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemImageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->image_id,
            'item_id' => $this->item_id,
            'image_url' => $this->image_url,
            'is_primary' => (bool) $this->is_primary,
            'created_at' => $this->created_at,
        ];
    }
}
