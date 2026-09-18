<?php

namespace App\Domain\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = ['quotation_id', 'description', 'qty', 'unit_price_minor', 'total_minor'];

    protected function casts(): array
    {
        return [
            'description' => 'array',
            'qty' => 'integer',
            'unit_price_minor' => 'integer',
            'total_minor' => 'integer',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
