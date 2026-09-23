<?php

namespace App\Domain\Finance\Models;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Catalog\Models\Partner;
use App\Models\Branch;
use App\Models\User;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VendorPayment extends Model
{
    use BelongsToBranch, LogsActivity, SoftDeletes;

    protected $table = 'vendor_payments';

    protected $fillable = [
        'branch_id',
        'booking_id',
        'partner_id',
        'amount_minor',
        'currency',
        'fx_rate',
        'idr_equivalent_minor',
        'description',
        'proof_file',
        'paid_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'idr_equivalent_minor' => 'integer',
            'fx_rate' => 'decimal:8',
            'paid_at' => 'date',
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

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
