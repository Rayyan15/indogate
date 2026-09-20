<?php

namespace App\Domain\Fleet\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vehicle extends Model
{
    use BelongsToBranch, LogsActivity, SoftDeletes;

    protected $table = 'vehicles';

    protected $fillable = [
        'branch_id',
        'driver_id',
        'plate',
        'plate_number',
        'type',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('fleet')
            ->logFillable();
    }

    public function getPlateAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['plate_number'] ?? null;
    }

    public function setPlateAttribute(?string $value): void
    {
        $this->attributes['plate'] = $value;
        $this->attributes['plate_number'] = $value;
    }

    public function setPlateNumberAttribute(?string $value): void
    {
        $this->attributes['plate_number'] = $value;
        if (! isset($this->attributes['plate']) || empty($this->attributes['plate'])) {
            $this->attributes['plate'] = $value;
        }
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class, 'vehicle_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
