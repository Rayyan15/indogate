<?php

namespace App\Domain\Pricing\Models;

use App\Enums\SeasonType;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Season extends Model
{
    use BelongsToBranch, LogsActivity;

    protected $fillable = ['branch_id', 'name', 'date_from', 'date_to', 'type'];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'type' => SeasonType::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public static function resolveFor(int $branchId, \DateTimeInterface $date): ?self
    {
        return static::query()
            ->where('branch_id', $branchId)
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->first();
    }
}
