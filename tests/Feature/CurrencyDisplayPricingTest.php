<?php

namespace Tests\Feature;

use App\Domain\Pricing\Converter;
use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Domain\Pricing\Money;
use App\Support\Storefront\StorefrontCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CurrencyDisplayPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function rate(string $code, string $rate, array $extra = []): ExchangeRate
    {
        return ExchangeRate::create(['currency' => $code, 'rate' => $rate, 'source' => 'manual', 'effective_from' => now()->subMinute()] + $extra);
    }

    public function test_fetch_skips_currency_with_active_pin_and_resumes_after_expiry(): void
    {
        Http::fake(['cdn.jsdelivr.net/*' => Http::response(['idr' => ['usd' => 1 / 16000]])]);
        $this->rate('USD', '15500', ['pinned_until' => now()->addDay()]);

        $this->artisan('fx:fetch')->assertSuccessful();
        $this->assertSame('15500.00000000', ExchangeRate::currentFor('USD')->rate);

        $this->travel(2)->days();
        $this->artisan('fx:fetch')->assertSuccessful();
        $this->assertSame('16000.00000000', ExchangeRate::currentFor('USD')->rate);
        $this->assertSame('api:fawazahmed0', ExchangeRate::currentFor('USD')->source);
    }

    public function test_rate_change_is_logged_with_old_new_and_percent(): void
    {
        $this->rate('USD', '16000');
        $this->rate('USD', '16800');

        $log = Activity::where('log_name', 'pricing')->where('description', 'exchange_rate_changed')->latest('id')->first();
        $this->assertSame('5.0000', number_format($log->properties['change_percent'], 4, '.', ''));
        $this->assertSame('manual', $log->properties['source']);
        $this->assertEquals('16000.00000000', $log->properties['old_rate']);
    }

    public function test_spread_and_rounding_apply_to_selling_price_only(): void
    {
        $this->rate('USD', '16000');
        Currency::find('USD')->update(['spread_bps' => 150, 'display_rounding' => 5]);
        $idr = Money::of(16_000_000, 'IDR'); // 1000 USD market

        $this->assertSame(100000, (new Converter)->toDisplayCurrency($idr, 'USD')->amountMinor);
        // 1000 * 1.015 = 1015 -> rounded up to a multiple of 5 = 1015
        $this->assertSame(101500, (new Converter)->toSellingPrice($idr, 'USD')->amountMinor);
        // 1001 USD * 1.015 = 1016.015 -> 1020
        $this->assertSame(102000, (new Converter)->toSellingPrice(Money::of(16_016_000, 'IDR'), 'USD')->amountMinor);
    }

    public function test_locked_rate_conversion_ignores_spread(): void
    {
        $locked = $this->rate('USD', '16000');
        Currency::find('USD')->update(['spread_bps' => 500, 'display_rounding' => 10]);

        $this->assertSame(100000, (new Converter)->toDisplayCurrency(Money::of(16_000_000, 'IDR'), 'USD', $locked)->amountMinor);
    }

    public function test_idr_rounds_up_to_thousand(): void
    {
        Currency::find('IDR')->update(['display_rounding' => 1000]);

        $this->assertSame(1_235_000, (new Converter)->toSellingPrice(Money::of(1_234_001, 'IDR'), 'IDR')->amountMinor);
    }

    public function test_storefront_list_derives_from_active_currencies_with_rates(): void
    {
        $this->assertNotContains('AED', StorefrontCurrency::supported());

        ExchangeRate::query()->delete();
        Currency::updateOrCreate(['code' => 'AED'], ['symbol' => 'AED', 'decimal_places' => 2, 'is_active' => true]);
        $this->rate('AED', '4350');
        $this->rate('EUR', '18000');
        $this->rate('USD', '16000');
        Currency::updateOrCreate(['code' => 'EUR'], ['symbol' => 'EUR', 'decimal_places' => 2, 'is_active' => true]);
        Currency::find('USD')->update(['is_active' => false]);

        $this->assertSame(['AED', 'EUR', 'IDR'], StorefrontCurrency::supported());
        StorefrontCurrency::set('AED');
        $this->assertSame('AED', StorefrontCurrency::current());
        StorefrontCurrency::set('USD');
        $this->assertSame('AED', StorefrontCurrency::current());
    }

    public function test_format_uses_currency_decimals_not_hundred(): void
    {
        $this->rate('KRW', '11.5');
        $this->rate('AED', '4350');
        $krw = StorefrontCurrency::format(11_500_000, 'KRW', 'en');
        $this->assertStringContainsString('1,000,000', $krw);
        $this->assertStringContainsString('1,000.00', StorefrontCurrency::format(4_350_000, 'AED', 'en'));
    }

    public function test_hotel_currency_defaults_to_idr_and_is_fillable(): void
    {
        $this->assertSame('IDR', \App\Models\Hotel::create(['name' => 'H', 'location' => 'Makkah', 'star_rating' => 5, 'base_price_per_night' => 100])->fresh()->currency);
    }
}
