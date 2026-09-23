<?php

namespace App\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Storefront (cart) booking. Payment is a single manual-transfer proof on
 * the row itself (payment_* columns) until a payment gateway replaces it;
 * the `payments` table belongs to package_bookings.
 */
class Booking extends Model
{
    use BelongsToBranch, SoftDeletes;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAYMENT_SUBMITTED = 'payment_submitted';

    public const STATUS_PAYMENT_REJECTED = 'payment_rejected';

    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'branch_id', 'booking_number', 'customer_id', 'driver_id', 'status', 'total_amount', 'currency',
        'payment_proof_path', 'payment_submitted_at', 'payment_verified_by', 'payment_verified_at', 'payment_rejection_reason',
    ];

    protected $casts = [
        'payment_submitted_at' => 'datetime',
        'payment_verified_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function items()
    {
        return $this->hasMany(BookingItem::class);
    }

    public function paymentVerifier()
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function canSubmitPayment(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_PAYMENT, self::STATUS_PAYMENT_REJECTED], true);
    }
}
