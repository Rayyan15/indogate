<?php

namespace Tests\Unit;

use App\Domain\Pricing\Converter;
use App\Domain\Pricing\Exceptions\ExchangeRateNotFoundException;
use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Domain\Pricing\Money;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConverterTest extends TestCase
{
    use RefreshDatabase;

    public function test_converts_idr_to_target_currency_using_latest_rate(): void
    {
        $user = User::factory()->create();
        Currency::create(['code' => 'USD', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]);
        ExchangeRate::create(['currency' => 'USD', 'rate' => '15000.00000000', 'effective_from' => now()->subDay(), 'created_by' => $user->id]);

        $result = (new Converter)->toDisplayCurrency(Money::of(15_000_000, 'IDR'), 'USD');

        $this->assertSame('USD', $result->currency);
        $this->assertSame(100000, $result->amountMinor);
    }

    public function test_idr_to_idr_is_passthrough(): void
    {
        $result = (new Converter)->toDisplayCurrency(Money::of(500_000, 'IDR'), 'IDR');

        $this->assertSame(500_000, $result->amountMinor);
        $this->assertSame('IDR', $result->currency);
    }

    public function test_missing_rate_throws_exception(): void
    {
        $this->expectException(ExchangeRateNotFoundException::class);

        (new Converter)->toDisplayCurrency(Money::of(1_000_000, 'IDR'), 'SAR');
    }

    public function test_locked_rate_is_used_over_latest_rate(): void
    {
        $user = User::factory()->create();
        Currency::create(['code' => 'USD', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]);

        $oldRate = ExchangeRate::create(['currency' => 'USD', 'rate' => '15000.00000000', 'effective_from' => now()->subDays(10), 'created_by' => $user->id]);
        ExchangeRate::create(['currency' => 'USD', 'rate' => '16000.00000000', 'effective_from' => now(), 'created_by' => $user->id]);

        $result = (new Converter)->toDisplayCurrency(Money::of(15_000_000, 'IDR'), 'USD', $oldRate);

        $this->assertSame(100000, $result->amountMinor);
    }
}
