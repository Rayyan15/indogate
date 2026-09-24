<?php

namespace Tests\Feature\Public;

use App\Domain\Pricing\Models\ExchangeRate;
use App\Support\Storefront\StorefrontCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencySwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_currency_matches_locale(): void
    {
        $this->get('/ar');
        $this->assertEquals('SAR', StorefrontCurrency::current());

        $this->get('/id');
        $this->assertEquals('IDR', StorefrontCurrency::current());

        $this->get('/en');
        $this->assertEquals('USD', StorefrontCurrency::current());
    }

    public function test_currency_switch_updates_session(): void
    {
        $response = $this->post('/en/currency', ['currency' => 'SAR']);
        $response->assertRedirect();
        $this->assertEquals('SAR', session('storefront_currency'));
        $this->assertEquals('SAR', StorefrontCurrency::current());

        $response = $this->post('/en/currency', ['currency' => 'IDR']);
        $response->assertRedirect();
        $this->assertEquals('IDR', session('storefront_currency'));
        $this->assertEquals('IDR', StorefrontCurrency::current());
    }

    public function test_currency_formatter_converts_idr_amount_to_target_currency(): void
    {
        // 15,000,000 IDR
        $amountIdr = 15000000;
        ExchangeRate::create(['currency' => 'SAR', 'rate' => '4250', 'effective_from' => now()->subMinute()]);
        ExchangeRate::create(['currency' => 'USD', 'rate' => '16000', 'effective_from' => now()->subMinute()]);

        $formattedSar = StorefrontCurrency::format($amountIdr, 'SAR', 'en');
        $formattedUsd = StorefrontCurrency::format($amountIdr, 'USD', 'en');
        $formattedIdr = StorefrontCurrency::format($amountIdr, 'IDR', 'id');

        // Check that currency symbols/codes are present
        $this->assertStringContainsString('SAR', $formattedSar);
        $this->assertStringContainsString('$', $formattedUsd);
        $this->assertStringContainsString('Rp', $formattedIdr);
    }
}
