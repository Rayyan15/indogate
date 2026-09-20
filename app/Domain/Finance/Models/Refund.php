<?php

namespace App\Domain\Finance\Models;

use App\Domain\Booking\Models\PackageBooking;
use App\Models\Branch;
use App\Models\User;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Refund extends Model
{
    use BelongsToBranch, LogsActivity;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected $table = 'refunds';

    protected $fillable = [
        'branch_id',
        'booking_id',
        'payment_id',
        'amount_minor',
        'currency',
        'fx_rate',
        'idr_equivalent_minor',
        'reason',
        'processed_by',
        'status',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'idr_equivalent_minor' => 'integer',
            'fx_rate' => 'decimal:8',
            'refunded_at' => 'datetime',
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
