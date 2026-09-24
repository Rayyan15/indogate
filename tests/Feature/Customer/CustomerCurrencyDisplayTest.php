<?php

namespace Tests\Feature\Customer;

use App\Domain\Pricing\Models\ExchangeRate;
use App\Models\Branch;
use App\Models\FlightRoute;
use App\Models\User;
use App\Support\Storefront\StorefrontCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCurrencyDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function setup2(): array
    {
        $this->seed();
        ExchangeRate::create(['currency' => 'USD', 'rate' => '16000', 'source' => 'manual', 'effective_from' => now()->subMinute()]);
        $branch = Branch::first();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('Customer');
        $flight = FlightRoute::create([
            'branch_id' => $branch->id, 'airline' => 'GA', 'origin' => 'CGK', 'destination' => 'DPS',
            'departure_at' => now()->addDays(5), 'base_price' => 1_600_000, 'seat_quota' => 5,
        ]);

        return [$user, $flight];
    }

    public function test_switcher_and_converted_price_on_search_and_cart_with_idr_total(): void
    {
        [$user, $flight] = $this->setup2();
        $this->actingAs($user)->withSession(['storefront_currency' => 'USD']);

        $this->get(route('search.index', ['locale' => 'en', 'type' => 'flights']))
            ->assertOk()->assertSee('name="currency"', false)->assertSee('$100', false)
            ->assertDontSee('IDR 1,600,000', false);

        $this->withSession(['cart' => [['bookable_type' => FlightRoute::class, 'bookable_id' => $flight->id, 'name' => 'GA', 'price' => 1_600_000, 'quantity' => 1]]]);
        $this->get(route('cart.index', ['locale' => 'en']))
            ->assertOk()->assertSee('$100', false)->assertSee('IDR 1,600,000', false)->assertSee('charged in IDR', false);
    }

    public function test_unrated_currency_falls_back_to_idr(): void
    {
        $this->seed();
        $this->assertStringContainsString('1,600,000', StorefrontCurrency::format(1_600_000, 'EUR', 'en'));
    }
}
