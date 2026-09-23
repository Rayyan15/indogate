<?php

namespace App\Domain\Booking\Models;

use App\Domain\Finance\Fx;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Models\Refund;
use App\Domain\Finance\Models\VendorPayment;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Lead\Models\Quotation;
use App\Enums\BookingStatus;
use App\Models\User;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PackageBooking extends Model
{
    use BelongsToBranch, LogsActivity;

    protected $fillable = [
        'branch_id', 'created_by', 'quotation_id', 'code', 'status',
        'departure_date', 'return_date', 'total_minor', 'currency',
        'driver_gender_preference',
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

    public function assignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class, 'booking_id');
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(DriverAssignment::class, 'booking_id')
            ->where('status', '!=', DriverAssignment::STATUS_CANCELLED)
            ->latestOfMany();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_id');
    }

    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class, 'booking_id');
    }

    public function vendorPayments(): HasMany
    {
        return $this->hasMany(VendorPayment::class, 'booking_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'booking_id');
    }

    /**
     * Rate used to express other-currency money in this booking's currency:
     * the rate locked on the quotation, so balances don't drift with
     * today's exchange rate (bug-review BF-15). Falls back to the current
     * rate only for legacy bookings without a quotation.
     */
    public function lockedRate(): float
    {
        if (strtoupper($this->currency) === 'IDR') {
            return 1.0;
        }

        $quotation = $this->quotation;
        $locked = $quotation && strtoupper($quotation->currency) === strtoupper($this->currency)
            ? (float) $quotation->locked_rate
            : 0.0;

        return $locked > 0 ? $locked : Fx::rate($this->currency);
    }

    /**
     * Converts an amount (given in its own currency plus its IDR
     * equivalent) into this booking's currency minor units.
     */
    public function toBookingCurrencyMinor(string $currency, int $amountMinor, int $idrMinor): int
    {
        if (strtoupper($currency) === strtoupper($this->currency)) {
            return $amountMinor;
        }

        return Fx::fromIdrMinor($idrMinor, $this->currency, $this->lockedRate());
    }

    public function totalPaidMinor(): int
    {
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();
        $refunds = $this->relationLoaded('refunds') ? $this->refunds : $this->refunds()->get();

        $paid = $payments->where('status', Payment::STATUS_VERIFIED)
            ->sum(fn ($p) => $this->toBookingCurrencyMinor($p->currency, (int) $p->amount_minor, (int) $p->idr_equivalent_minor));

        $refunded = $refunds->where('status', Refund::STATUS_COMPLETED)
            ->sum(fn ($r) => $this->toBookingCurrencyMinor($r->currency, (int) $r->amount_minor, (int) $r->idr_equivalent_minor));

        return max(0, (int) $paid - (int) $refunded);
    }

    public function remainingBalanceMinor(): int
    {
        return max(0, (int) $this->total_minor - $this->totalPaidMinor());
    }

    public function isFullyPaid(): bool
    {
        return $this->totalPaidMinor() >= (int) $this->total_minor;
    }
}
