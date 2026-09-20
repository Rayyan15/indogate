<?php

namespace App\Domain\Finance\Models;

use App\Domain\Booking\Models\PackageBooking;
use App\Models\Branch;
use App\Models\User;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use BelongsToBranch, LogsActivity, SoftDeletes;

    public const TYPE_DOWN_PAYMENT = 'down_payment';

    public const TYPE_FULL_PAYMENT = 'full_payment';

    public const TYPE_INSTALLMENT = 'installment';

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'payments';

    protected $fillable = [
        'branch_id',
        'booking_id',
        'payment_intent_id',
        'type',
        'amount_minor',
        'currency',
        'fx_rate',
        'idr_equivalent_minor',
        'channel_fee_minor',
        'proof_file',
        'channel',
        'notes',
        'status',
        'created_by',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'idr_equivalent_minor' => 'integer',
            'channel_fee_minor' => 'integer',
            'fx_rate' => 'decimal:8',
            'verified_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('finance')
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

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class, 'payment_intent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'payment_id');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }
}
