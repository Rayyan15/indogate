<?php

namespace App\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'driver_id', 'type', 'plate_number', 'capacity'];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
