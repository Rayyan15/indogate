<?php

namespace App\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Hotel extends Model
{
    use BelongsToBranch, HasFactory, HasTranslations;

    protected $fillable = ['branch_id', 'name', 'description', 'location', 'star_rating', 'base_price_per_night', 'currency'];

    /**
     * PRD M2 step 9: spatie/laravel-translatable, not a plain array cast —
     * gives per-locale accessors ($hotel->name resolves current locale,
     * getTranslation('name', 'ar') for an explicit one).
     */
    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
