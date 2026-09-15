<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
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
