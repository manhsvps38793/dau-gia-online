<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'title', 'thumbnail',
        'content', 'author', 'is_published'
    ];

    public function category()
    {
        return $this->belongsTo(NewsCategory::class, 'category_id');
    }
}
