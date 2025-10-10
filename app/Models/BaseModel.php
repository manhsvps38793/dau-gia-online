<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BaseModel extends Model
{
    public function serializeDate(\DateTimeInterface $date)
    {
        // Ép kiểu rõ ràng để IDE/PHP hiểu là Carbon
        $carbonDate = Carbon::instance($date)->setTimezone('Asia/Ho_Chi_Minh');
        return $carbonDate->format('Y-m-d\TH:i:s');
    }
    protected function asDateTime($value)
{
    return parent::asDateTime($value)->setTimezone('Asia/Ho_Chi_Minh');
}

}
