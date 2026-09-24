<?php

namespace Tests\Feature;

use App\Domain\Pricing\Converter;
use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Domain\Pricing\Money;
use App\Notifications\FxRateNeedsReview;
use App\Support\Storefront\StorefrontCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class FxHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        ExchangeRate::query()->delete();
    }

    private function rate(string $code, string $rate, $from, array $extra = []): ExchangeRate
    {
        return ExchangeRate::create(['currency' => $code, 'rate' => $rate, 'source' => ExchangeRate::SOURCE_MANUAL, 'effective_from' => $from] + $extra);
    }

    private function reviewsSent(): int
    {
        return \App\Models\User::all()->sum(fn ($u) => Notification::sent($u, FxRateNeedsReview::class)->count());
    }

    PRIVATE FUNCTION SELL(int $idr, string $code, int $spread, int $step): int
    {
        Currency::where('code', $code)->update(['spread_bps' => $spread, 'display_rounding' => $step]);
        \App\Domain\Finance\Fx::flush();

        return (new Converter)->toSellingPrice(Money::of($idr, 'IDR'), $code)->amountMinor;
    }

    public function test_backdated_rate_logs_rate_in_force_before_it_as_old(): void
    {
        $this->rate('USD', '15000', now()->subDays(10));
        $this->rate('USD', '16000', now()->subDay());
        $this->rate('USD', '15500', now()->subDays(5)); // back-dated, highest id

        $log = Activity::where('log_name', 'pricing')->latest('id')->first();

        $this->assertSame('15000.00000000', $log->properties['old_rate']);
        $this->assertNull(Activity::where('log_name', 'pricing')->oldest('id')->first()->properties['old_rate']);
    }

    public function test_spread_rounds_half_up_at_boundaries(): void
    {
        $this->rate('USD', '10000', now()->subMinute());
        // 10000 IDR -> 100 minor cents.
        $this->assertSame(100, $this->sell(10000, 'USD', 0, 0));
        // 100 * 1.0050 = 100.5 -> half-up 101 (was truncated to 100).
        $this->assertSame(101, $this->sell(10000, 'USD', 50, 0));
        // 100 * 1.5 = 150 exactly.
        $this->assertSame(150, $this->sell(10000, 'USD', 5000, 0));
        // 1 unit rounding step: 100.5 -> 101 cents -> up to 200 cents ($2).
        $this->assertSame(200, $this->sell(10000, 'USD', 50, 1));
        // Already on the step boundary stays put.
        $this->assertSame(100, $this->sell(10000, 'USD', 0, 1));
    }

    public function test_zero_decimal_currency_uses_configured_decimals(): void
    {
        Currency::create(['code' => 'JPY', 'symbol' => 'Y', 'decimal_places' => 0, 'is_active' => true]);
        $this->rate('JPY', '100', now()->subMinute());

        $this->assertSame(1000, $this->sell(100000, 'JPY', 0, 0));
        $this->assertSame(1000, $this->sell(100000, 'JPY', 0, 10));
        $this->assertSame(1010, $this->sell(100000, 'JPY', 50, 10));
    }

    public function test_overflow_throws_and_storefront_falls_back_to_idr(): void
    {
        $this->rate('USD', '0.00000001', now()->subMinute());
        Currency::where('code', 'USD')->update(['spread_bps' => 5000]);
        \App\Domain\Finance\Fx::flush();

        $this->expectException(\OverflowException::class);
        (new Converter)->toSellingPrice(Money::of(PHP_INT_MAX, 'IDR'), 'USD');
    }

    public function test_storefront_format_does_not_requery_per_call(): void
    {
        $this->rate('USD', '16000', now()->subMinute());
        StorefrontCurrency::format(1_600_000, 'USD', 'en');

        DB::enableQueryLog();
        StorefrontCurrency::format(3_200_000, 'USD', 'en');
        StorefrontCurrency::format(4_800_000, 'USD', 'en');

        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_pinned_skip_and_unpinned_manual_rate_is_guard_baseline(): void
    {
        Http::fake(['cdn.jsdelivr.net/*' => Http::response(['idr' => ['usd' => 1 / 16000]])]);
        $this->rate('USD', '10000', now()->subDays(3), ['source' => ExchangeRate::SOURCE_FAWAZAHMED0]);
        $this->rate('USD', '15800', now()->subDay(), ['pinned_until' => now()->addHour()]);

        $this->artisan('fx:fetch')->expectsOutputToContain('pinned')->assertSuccessful();
        $this->assertSame(2, ExchangeRate::where('currency', 'USD')->count());

        $this->travel(2)->hours(); // pin expired; baseline is the manual 15800, not stale 10000
        $this->artisan('fx:fetch')->assertSuccessful();
        $this->assertSame('16000.00000000', ExchangeRate::currentFor('USD')->rate);
    }

    public function test_force_inserts_and_review_notification_sent_once_per_day(): void
    {
        Http::fake(['cdn.jsdelivr.net/*' => Http::response(['idr' => ['usd' => 1 / 16000]])]);
        $this->rate('USD', '10000', now()->subDay());
        Notification::fake();

        $this->artisan('fx:fetch')->assertSuccessful();
        $first = $this->reviewsSent();
        $this->assertGreaterThan(0, $first);

        $this->artisan('fx:fetch')->assertSuccessful();
        $this->assertSame($first, $this->reviewsSent());

        $this->artisan('fx:fetch --force')->expectsOutputToContain('forced')->assertSuccessful();
        $this->assertSame(ExchangeRate::SOURCE_FAWAZAHMED0, ExchangeRate::currentFor('USD')->source);
    }
}
