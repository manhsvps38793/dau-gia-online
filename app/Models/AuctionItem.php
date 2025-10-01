<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionItem extends Model
{
    protected $table = 'AuctionItems';
    protected $primaryKey = 'item_id';
    public $timestamps = false;

    protected $fillable = [
        'category_id', 'owner_id', 'name', 'description',
        'starting_price', 'image_url', 'status', 'created_at', 'deleted_at'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sessions()
    {
        return $this->hasMany(AuctionSession::class, 'item_id', 'item_id');
    }

}
