<?php

namespace Tests\Feature;

use App\Domain\Pricing\Models\ExchangeRate;
use App\Support\Storefront\StorefrontCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchExchangeRatesTest extends TestCase
{
    use RefreshDatabase;

    private const PRIMARY = 'cdn.jsdelivr.net/*';

    private const FALLBACK = 'latest.currency-api.pages.dev/*';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function payload(): array
    {
        return ['date' => '2026-09-23', 'idr' => ['usd' => 1 / 16000, 'sar' => 1 / 4250, 'eur' => 0.00005]];
    }

    public function test_primary_source_inserts_rates(): void
    {
        Http::fake([self::PRIMARY => Http::response($this->payload())]);

        $this->artisan('fx:fetch')->assertSuccessful();

        $usd = ExchangeRate::currentFor('USD');
        $this->assertSame('16000.00000000', $usd->rate);
        $this->assertNull($usd->created_by);
        $this->assertSame('4250.00000000', ExchangeRate::currentFor('SAR')->rate);
        $this->assertNull(ExchangeRate::currentFor('IDR'));
    }

    public function test_fallback_used_when_primary_fails(): void
    {
        Http::fake([self::PRIMARY => Http::response('oops', 500), self::FALLBACK => Http::response($this->payload())]);

        $this->artisan('fx:fetch')->assertSuccessful();

        $this->assertSame('16000.00000000', ExchangeRate::currentFor('USD')->rate);
    }

    public function test_both_sources_fail_exits_non_zero_and_inserts_nothing(): void
    {
        Http::fake([self::PRIMARY => Http::response('oops', 500), self::FALLBACK => Http::response('not json')]);
        $before = ExchangeRate::count();

        $this->artisan('fx:fetch')->assertFailed();

        $this->assertSame($before, ExchangeRate::count());
    }

    public function test_large_change_is_skipped_and_same_rate_not_duplicated(): void
    {
        ExchangeRate::query()->delete();
        ExchangeRate::create(['currency' => 'USD', 'rate' => '10000', 'effective_from' => now()->subDay()]);
        ExchangeRate::create(['currency' => 'SAR', 'rate' => '4250', 'effective_from' => now()->subDay()]);
        Http::fake([self::PRIMARY => Http::response($this->payload())]);

        $this->artisan('fx:fetch')->expectsOutputToContain('needs review')->assertSuccessful();

        $this->assertSame('10000.00000000', ExchangeRate::currentFor('USD')->rate);
        $this->assertSame(1, ExchangeRate::where('currency', 'SAR')->count());
    }

    public function test_force_bypasses_guard_and_manual_rate_does_not_block_auto(): void
    {
        ExchangeRate::query()->delete();
        ExchangeRate::create(['currency' => 'USD', 'rate' => '10000', 'effective_from' => now()->subDay()]);
        Http::fake([self::PRIMARY => Http::response($this->payload())]);

        $this->artisan('fx:fetch --force')->expectsOutputToContain('forced')->assertSuccessful();

        $this->assertSame('16000.00000000', ExchangeRate::currentFor('USD')->rate);
    }

    public function test_skip_notifies_pricing_managers(): void
    {
        ExchangeRate::query()->delete();
        ExchangeRate::create(['currency' => 'USD', 'rate' => '10000', 'effective_from' => now()->subDay(), 'created_by' => null]);
        Http::fake([self::PRIMARY => Http::response($this->payload())]);
        \Illuminate\Support\Facades\Notification::fake();
        $u = \App\Models\User::permission('pricing.manage')->where('is_active', true)->whereNotNull('branch_id')->first();
        $this->assertNotNull($u);
        $this->assertGreaterThan(0, \App\Support\Notify::recipients($u->branch_id, "pricing.manage")->count());

        $this->artisan('fx:fetch')->assertSuccessful();

        \Illuminate\Support\Facades\Notification::assertSentTo($u, \App\Notifications\FxRateNeedsReview::class);
    }

    public function test_storefront_currency_without_rates_falls_back_to_idr(): void
    {
        ExchangeRate::query()->delete();

        $out = StorefrontCurrency::format(1_600_000, 'USD', 'en');

        $this->assertStringNotContainsString('$', $out);
        $this->assertStringContainsString('1,600,000', $out);
    }
}
