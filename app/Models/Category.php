<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends BaseModel
{
    protected $primaryKey = 'category_id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['name', 'description'];

    // Định nghĩa mối quan hệ với AuctionItem   
    public function auctionItems()
    {
        return $this->hasMany(AuctionItem::class, 'category_id', 'category_id');
    }
}
