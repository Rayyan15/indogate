<?php

namespace App\Domain\Pricing\Models;

use App\Domain\Finance\Fx;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Currency extends Model
{
    use LogsActivity;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['code', 'symbol', 'decimal_places', 'is_active', 'spread_bps', 'display_rounding'];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
            'spread_bps' => 'integer',
            'display_rounding' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => Fx::flush();
        static::saved($flush);
        static::deleted($flush);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
}
