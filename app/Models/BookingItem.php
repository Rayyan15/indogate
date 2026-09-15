<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_id',
        'bookable_type',
        'bookable_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    public function bookable()
    {
        return $this->morphTo();
    }
}
