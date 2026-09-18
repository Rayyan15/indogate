<?php

namespace App\Domain\Packaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class PackageDay extends Model
{
    use HasTranslations;

    protected $fillable = ['package_id', 'day_number', 'title', 'notes'];

    public array $translatable = ['title', 'notes'];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
