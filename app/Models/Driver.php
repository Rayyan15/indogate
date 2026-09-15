<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use SoftDeletes;

    protected $fillable = ['full_name', 'gender', 'phone', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
