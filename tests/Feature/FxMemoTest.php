<?php

namespace Tests\Feature;

use App\Domain\Finance\Fx;
use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FxMemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_and_decimals_hit_db_once_and_flush_on_write(): void
    {
        Currency::updateOrCreate(['code' => 'USD'], ['symbol' => '$', 'decimal_places' => 2, 'is_active' => true]);
        ExchangeRate::create(['currency' => 'USD', 'rate' => 16000, 'effective_from' => now()->subDay()]);
        Fx::flush();

        DB::enableQueryLog();
        for ($i = 0; $i < 50; $i++) {
            Fx::rate('USD');
            Fx::decimals('USD');
            Fx::toIdrMinor(100, 'USD', 16000.0);
        }
        $this->assertCount(2, DB::getQueryLog());

        ExchangeRate::create(['currency' => 'USD', 'rate' => 17000, 'effective_from' => now()->subHour()]);
        $this->assertSame(17000.0, Fx::rate('USD'));
    }
}
