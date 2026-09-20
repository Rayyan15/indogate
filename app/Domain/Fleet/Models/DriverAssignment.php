<?php

namespace App\Domain\Fleet\Models;

use App\Domain\Booking\Models\PackageBooking;
use App\Models\Branch;
use App\Support\Branch\BelongsToBranch;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DriverAssignment extends Model
{
    use BelongsToBranch, LogsActivity;

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'driver_assignments';

    protected $fillable = [
        'branch_id',
        'booking_id',
        'driver_id',
        'vehicle_id',
        'date_from',
        'date_to',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('fleet')
            ->logFillable();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(PackageBooking::class, 'booking_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    public function scopeOverlapping(Builder $query, string|CarbonInterface $dateFrom, string|CarbonInterface $dateTo): Builder
    {
        $dateFromStr = $dateFrom instanceof CarbonInterface ? $dateFrom->toDateString() : (string) $dateFrom;
        $dateToStr = $dateTo instanceof CarbonInterface ? $dateTo->toDateString() : (string) $dateTo;

        return $query->where('status', '!=', self::STATUS_CANCELLED)
            ->whereDate('date_from', '<=', $dateToStr)
            ->whereDate('date_to', '>=', $dateFromStr);
    }
}
