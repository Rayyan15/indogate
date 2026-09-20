<?php

namespace App\Domain\Booking\Models;

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

    public function totalPaidMinor(): int
    {
        $totalPaid = (int) $this->payments()
            ->where('status', Payment::STATUS_VERIFIED)
            ->sum('amount_minor');

        $totalRefunded = (int) $this->refunds()
            ->where('status', Refund::STATUS_COMPLETED)
            ->sum('amount_minor');

        return max(0, $totalPaid - $totalRefunded);
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
