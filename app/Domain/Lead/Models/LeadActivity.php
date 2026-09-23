<?php

namespace App\Domain\Lead\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    public $timestamps = false;

    // The DB default (useCurrent) stamps MySQL's local time, not the app's UTC.
    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->created_at ??= now());
    }

    protected $fillable = ['lead_id', 'user_id', 'type', 'note'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
