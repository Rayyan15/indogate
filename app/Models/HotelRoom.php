<?php

namespace App\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class HotelRoom extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'hotel_id', 'room_type', 'capacity', 'base_price'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
