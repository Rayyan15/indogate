<?php

namespace App\Domain\Booking\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingNote extends Model
{
    public $timestamps = false;

    // The DB default (useCurrent) stamps MySQL's local time, not the app's UTC.
    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->created_at ??= now());
    }

    protected $fillable = ['booking_id', 'user_id', 'note'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
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
