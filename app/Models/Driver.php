<?php

namespace App\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use BelongsToBranch, SoftDeletes;

    protected $fillable = ['branch_id', 'full_name', 'gender', 'phone', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
