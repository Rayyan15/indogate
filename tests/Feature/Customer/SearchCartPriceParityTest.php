<?php

namespace Tests\Feature\Customer;

use App\Http\Controllers\Customer\CartController;
use App\Models\Branch;
use App\Models\FlightRoute;
use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchCartPriceParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_price_equals_cart_price_with_branch_markup(): void
    {
        $this->seed();
        $branch = Branch::first();
        $other = Branch::where('id', '!=', $branch->id)->firstOrFail();
        foreach ([[$branch->id, 10], [$other->id, 200]] as [$b, $m]) {
            PricingRule::withoutGlobalScopes()->create([
                'branch_id' => $b, 'service_type' => 'all', 'markup_percent' => $m,
                'season_start' => today()->subDay(), 'season_end' => today()->addDay(),
            ]);
        }
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('Customer');
        $flight = FlightRoute::create([
            'branch_id' => $branch->id, 'airline' => 'GA', 'origin' => 'CGK', 'destination' => 'DPS',
            'departure_at' => now()->addDays(5), 'base_price' => 1_000_000, 'seat_quota' => 5,
        ]);

        $cartPrice = CartController::resolveItemPrice(FlightRoute::class, $flight->id);
        $this->assertSame(1_100_000.0, $cartPrice);

        $this->actingAs($user)->get(route('search.index', ['locale' => 'id', 'type' => 'flights']))
            ->assertOk()
            ->assertSee(\App\Support\Storefront\StorefrontCurrency::format((int) $cartPrice), false);
    }
}
