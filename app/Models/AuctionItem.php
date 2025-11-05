<?php

namespace App\Models;

class AuctionItem extends BaseModel
{
    protected $table = 'AuctionItems';
    protected $primaryKey = 'item_id';
    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'owner_id',
        'auction_org_id',
        'name',
        'description',
        'starting_price',
        'image_url',
        'url_file',
        'status',
        'created_at',
        'deleted_at',
        'created_user'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'user_id');
    }

    public function sessions()
    {
        return $this->hasMany(AuctionSession::class, 'item_id', 'item_id');
    }

    public function images()
    {
        return $this->hasMany(ItemImage::class, 'item_id', 'item_id');
    }
// AuctionItem.php
public function createdUser()
{
    return $this->belongsTo(User::class, 'created_user', 'user_id');
}


}
