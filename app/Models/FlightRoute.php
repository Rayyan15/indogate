<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlightRoute extends Model
{
    use SoftDeletes;

    protected $fillable = ['airline', 'origin', 'destination', 'departure_at', 'base_price', 'seat_quota'];

    protected $casts = [
        'departure_at' => 'datetime',
    ];
}
