<?php

namespace App\Console\Commands;

use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\ExchangeRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Daily IDR rates from fawazahmed0 currency-api (CC0). Insert-only; big jumps need manual review. */
class FetchExchangeRates extends Command
{
    protected $signature = 'fx:fetch';

    protected $description = 'Fetch daily exchange rates (IDR per unit) from currency-api';

    private const SOURCES = [
        'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/idr.min.json',
        'https://latest.currency-api.pages.dev/v1/currencies/idr.min.json',
    ];

    public function handle(): int
    {
        $data = null;
        foreach (self::SOURCES as $url) {
            try {
                $data = Http::timeout(10)->get($url)->throw()->json('idr');
            } catch (\Throwable) {
                $data = null;
            }
            if (is_array($data)) {
                break;
            }
        }

        if (! is_array($data)) {
            $this->error('All FX sources failed.');
            Log::error('fx:fetch: all sources failed');

            return self::FAILURE;
        }

        $maxChange = (float) config('services.fx.max_change_percent', 5);

        foreach (Currency::where('is_active', true)->where('code', '!=', 'IDR')->pluck('code') as $code) {
            $value = (float) ($data[strtolower($code)] ?? 0);
            if ($value <= 0) {
                continue;
            }
            $rate = number_format(1 / $value, 8, '.', '');
            $prev = ExchangeRate::currentFor($code);

            if ($prev && bccomp($prev->rate, $rate, 8) === 0) {
                $this->line("{$code}: unchanged {$rate}");

                continue;
            }
            if ($prev && abs($rate / $prev->rate - 1) * 100 > $maxChange) {
                $this->warn("{$code}: {$prev->rate} -> {$rate} needs review (skipped)");
                Log::warning("fx:fetch {$code} change exceeds {$maxChange}%, needs review", ['old' => $prev->rate, 'new' => $rate]);

                continue;
            }

            ExchangeRate::create(['currency' => $code, 'rate' => $rate, 'effective_from' => now(), 'created_by' => null]);
            $this->info("{$code}: {$rate}");
        }

        return self::SUCCESS;
    }
}
