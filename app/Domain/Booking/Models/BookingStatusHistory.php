<?php

namespace App\Domain\Booking\Models;

use App\Enums\BookingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStatusHistory extends Model
{
    public $timestamps = false;

    // The DB default (useCurrent) stamps MySQL's local time, not the app's UTC.
    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->created_at ??= now());
    }

    protected $fillable = ['booking_id', 'from_status', 'to_status', 'user_id', 'reason'];

    protected function casts(): array
    {
        return [
            'from_status' => BookingStatus::class,
            'to_status' => BookingStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(PackageBooking::class, 'booking_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
