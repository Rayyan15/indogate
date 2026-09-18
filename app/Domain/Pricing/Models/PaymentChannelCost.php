<?php

namespace App\Domain\Pricing\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentChannel;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentChannelCost extends Model
{
    use LogsActivity;

    protected $fillable = ['channel', 'percent_fee', 'flat_fee_minor', 'currency'];

    protected function casts(): array
    {
        return [
            'channel' => PaymentChannel::class,
            'percent_fee' => 'integer',
            'flat_fee_minor' => MoneyCast::class.':currency',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
}
