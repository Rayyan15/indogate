<?php

namespace App\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingRule extends Model
{
    use BelongsToBranch, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'service_type',
        'season_start',
        'season_end',
        'markup_percent',
        'tier',
    ];

    protected $casts = [
        'season_start' => 'date',
        'season_end' => 'date',
        'markup_percent' => 'decimal:2',
    ];
}
