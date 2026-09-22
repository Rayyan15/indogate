<?php

namespace Tests\Feature\Customer;

use App\Models\Branch;
use App\Models\FlightRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartCheckoutPriceTamperingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_add_ignores_client_tampered_price(): void
    {
        $this->seed();
        $branch = Branch::first();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('Customer');

        $flight = FlightRoute::create([
            'branch_id' => $branch->id,
            'airline' => 'Garuda Indonesia',
            'origin' => 'CGK',
            'destination' => 'DPS',
            'departure_at' => now()->addDays(5),
            'base_price' => 1_500_000,
            'seat_quota' => 20,
        ]);

        // Attacker attempts to send price = 1 IDR
        $response = $this->actingAs($user)->post(route('cart.add', ['locale' => 'id']), [
            'bookable_type' => FlightRoute::class,
            'bookable_id' => $flight->id,
            'name' => 'Flight CGK - DPS',
            'price' => 1,
            'quantity' => 2,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('cart');
        $cart = $this->app['session']->get('cart');
        $this->assertNotEmpty($cart);
        $this->assertSame(1_500_000.0, (float) $cart[0]['price']);
    }

    public function test_checkout_recalculates_price_even_if_session_tampered(): void
    {
        $this->seed();
        $branch = Branch::first();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('Customer');

        $flight = FlightRoute::create([
            'branch_id' => $branch->id,
            'airline' => 'Garuda Indonesia',
            'origin' => 'CGK',
            'destination' => 'DPS',
            'departure_at' => now()->addDays(5),
            'base_price' => 2_000_000,
            'seat_quota' => 20,
        ]);

        // Inject manipulated cart into session with price = 100
        $sessionCart = [
            [
                'bookable_type' => FlightRoute::class,
                'bookable_id' => $flight->id,
                'name' => 'Flight CGK - DPS',
                'price' => 100,
                'quantity' => 2,
            ],
        ];

        $response = $this->actingAs($user)
            ->withSession(['cart' => $sessionCart])
            ->post(route('checkout.store', ['locale' => 'id']));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', [
            'total_amount' => 4_000_000, // 2_000_000 * 2, not 100 * 2
        ]);
        $this->assertDatabaseHas('booking_items', [
            'unit_price' => 2_000_000,
            'subtotal' => 4_000_000,
        ]);
    }

    public function test_checkout_handles_deleted_cart_items_gracefully(): void
    {
        $this->seed();
        $branch = Branch::first();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('Customer');

        // Cart with non-existent / deleted flight ID 99999
        $staleCart = [
            [
                'bookable_type' => FlightRoute::class,
                'bookable_id' => 99999,
                'name' => 'Deleted Flight',
                'price' => 1_000_000,
                'quantity' => 1,
            ],
        ];

        // Accessing checkout should not crash 404, but redirect to cart with error
        $response = $this->actingAs($user)
            ->withSession(['cart' => $staleCart])
            ->get(route('checkout.index', ['locale' => 'id']));

        $response->assertRedirect(route('cart.index', ['locale' => 'id']));
        $response->assertSessionHas('error');
    }
}
