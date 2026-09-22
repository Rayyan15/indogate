<?php

namespace App\Domain\Fleet\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Driver extends Model
{
    use BelongsToBranch, LogsActivity, SoftDeletes;

    public const DAILY_RATE_BASE = 500000;

    protected $table = 'drivers';

    protected $fillable = [
        'branch_id',
        'name',
        'full_name',
        'gender',
        'phone',
        'languages',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('fleet')
            ->logFillable();
    }

    public function getNameAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['full_name'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
        $this->attributes['full_name'] = $value;
    }

    public function setFullNameAttribute(?string $value): void
    {
        $this->attributes['full_name'] = $value;
        if (! isset($this->attributes['name']) || empty($this->attributes['name'])) {
            $this->attributes['name'] = $value;
        }
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class, 'driver_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByGender(Builder $query, string $gender): Builder
    {
        return $query->where('gender', $gender);
    }
}
