<?php

namespace App\Domain\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingGuest extends Model
{
    protected $fillable = ['booking_id', 'name', 'passport_number', 'passport_file', 'nationality', 'is_lead_guest'];

    /**
     * PRD M7: passport number must be encrypted at rest. Same technique as
     * the existing App\Models\Customer::$casts (only other encrypted-PII
     * precedent in this codebase).
     */
    protected $casts = [
        'passport_number' => 'encrypted',
        'is_lead_guest' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(PackageBooking::class, 'booking_id');
    }
}
