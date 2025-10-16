<?php

namespace App\Models;

class ItemImage extends BaseModel
{
    protected $table = 'ItemImages';
    protected $primaryKey = 'image_id';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'image_url',
        'is_primary',
        'created_at'
    ];

    protected $casts = [
        'is_primary' => 'boolean'
    ];

    public function item()
    {
        return $this->belongsTo(AuctionItem::class, 'item_id', 'item_id');
    }
}
