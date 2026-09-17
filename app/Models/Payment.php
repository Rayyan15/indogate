<?php

namespace App\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use BelongsToBranch, LogsActivity, SoftDeletes;

    protected $fillable = ['branch_id', 'booking_id', 'amount', 'status', 'verified_by', 'verified_at'];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function proofs()
    {
        return $this->hasMany(PaymentProof::class);
    }
}
