<?php

namespace App\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlightRoute extends Model
{
    use BelongsToBranch, SoftDeletes;

    protected $fillable = ['branch_id', 'airline', 'origin', 'destination', 'departure_at', 'base_price', 'seat_quota'];

    protected $casts = [
        'departure_at' => 'datetime',
    ];
}
