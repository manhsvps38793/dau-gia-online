<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends BaseModel
{
    protected $table = 'Reports';
    protected $primaryKey = 'report_id';
    public $timestamps = false;

    protected $fillable = [
        'generated_by',
        'report_type',
        'content',
        'created_at'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
