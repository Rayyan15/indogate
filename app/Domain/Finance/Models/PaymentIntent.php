<?php

namespace App\Domain\Finance\Models;

use App\Domain\Booking\Models\PackageBooking;
use App\Models\Branch;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentIntent extends Model
{
    use BelongsToBranch, LogsActivity;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $table = 'payment_intents';

    protected $fillable = [
        'branch_id',
        'booking_id',
        'provider',
        'public_token',
        'channel',
        'payment_type',
        'method',
        'amount_minor',
        'currency',
        'fx_rate',
        'channel_fee_minor',
        'status',
        'notes',
        'expires_at',
        'provider_reference',
        'paid_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'channel_fee_minor' => 'integer',
            'fx_rate' => 'decimal:8',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('finance')
            ->logFillable();
    }

    public function isPayable(): bool
    {
        return $this->status === self::STATUS_PENDING && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(PackageBooking::class, 'booking_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_intent_id');
    }
}
