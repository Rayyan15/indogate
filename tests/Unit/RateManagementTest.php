<?php

namespace Tests\Unit;

use App\Domain\Pricing\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_rate_row_is_never_overwritten_by_a_new_one(): void
    {
        $user = User::factory()->create();

        $old = ExchangeRate::create(['currency' => 'USD', 'rate' => '15000.00000000', 'effective_from' => now()->subDays(5), 'created_by' => $user->id]);
        ExchangeRate::create(['currency' => 'USD', 'rate' => '15800.00000000', 'effective_from' => now(), 'created_by' => $user->id]);

        $this->assertSame(2, ExchangeRate::where('currency', 'USD')->count());
        $this->assertSame('15000.00000000', $old->fresh()->rate);
    }

    public function test_current_for_returns_latest_rate_not_earliest(): void
    {
        $user = User::factory()->create();

        ExchangeRate::create(['currency' => 'SAR', 'rate' => '4000.00000000', 'effective_from' => now()->subDays(10), 'created_by' => $user->id]);
        $latest = ExchangeRate::create(['currency' => 'SAR', 'rate' => '4200.00000000', 'effective_from' => now()->subDay(), 'created_by' => $user->id]);

        $current = ExchangeRate::currentFor('SAR');

        $this->assertSame($latest->id, $current->id);
    }

    public function test_current_for_ignores_rates_effective_in_the_future(): void
    {
        $user = User::factory()->create();

        $past = ExchangeRate::create(['currency' => 'SAR', 'rate' => '4000.00000000', 'effective_from' => now()->subDay(), 'created_by' => $user->id]);
        ExchangeRate::create(['currency' => 'SAR', 'rate' => '5000.00000000', 'effective_from' => now()->addDays(3), 'created_by' => $user->id]);

        $current = ExchangeRate::currentFor('SAR');

        $this->assertSame($past->id, $current->id);
    }
}
