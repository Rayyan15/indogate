<?php

namespace App\Domain\Lead\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\User;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Lead extends Model
{
    use BelongsToBranch, LogsActivity;

    protected $fillable = [
        'branch_id', 'name', 'phone', 'country', 'locale',
        'source', 'status', 'assigned_to', 'lost_reason', 'follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => LeadSource::class,
            'status' => LeadStatus::class,
            'follow_up_at' => 'datetime',
        ];
    }

    /** Visual reminder flag only — no notification/email is sent for this. */
    public function isFollowUpDue(): bool
    {
        return $this->follow_up_at !== null
            && $this->follow_up_at->lte(now())
            && ! in_array($this->status, [LeadStatus::WON, LeadStatus::LOST], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('created_at');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }
}
