<?php

namespace App\Domain\Booking\Models;

use App\Domain\Lead\Models\Quotation;
use App\Enums\BookingStatus;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PackageBooking extends Model
{
    use BelongsToBranch, LogsActivity;

    protected $fillable = [
        'branch_id', 'quotation_id', 'code', 'status',
        'departure_date', 'return_date', 'total_minor', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'departure_date' => 'date',
            'return_date' => 'date',
            'total_minor' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(BookingGuest::class, 'booking_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(BookingNote::class, 'booking_id')->latest('created_at');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class, 'booking_id')->latest('created_at');
    }
}
