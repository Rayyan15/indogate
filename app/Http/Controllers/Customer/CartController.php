<?php

namespace App\Http\Controllers\Customer;

use App\Domain\Fleet\Models\Driver as DomainDriver;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\FlightRoute;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    public const ALLOWED_BOOKABLE_TYPES = [
        FlightRoute::class,
        Hotel::class,
        Driver::class,
    ];

    public function index()
    {
        $cart = session()->get('cart', []);

        return view('customer.cart.index', compact('cart'));
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'bookable_type' => ['required', 'string', Rule::in(self::ALLOWED_BOOKABLE_TYPES)],
            'bookable_id' => 'required|integer',
            'name' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        // Always resolve price server-side from database to prevent price tampering
        $validated['price'] = self::resolveItemPrice($validated['bookable_type'], (int) $validated['bookable_id']);

        $cart = session()->get('cart', []);
        $cart[] = $validated;
        session()->put('cart', $cart);

        return back()->with('success', 'Item added to cart.');
    }

    public static function resolveItemPrice(string $bookableType, int $bookableId): float
    {
        $activeRules = \App\Models\PricingRule::where('season_start', '<=', now())
            ->where('season_end', '>=', now())
            ->get();

        if ($bookableType === FlightRoute::class || is_a($bookableType, FlightRoute::class, true)) {
            $flight = FlightRoute::findOrFail($bookableId);
            $markup = $activeRules->filter(fn ($r) => in_array($r->service_type, ['flight', 'all']))->max('markup_percent') ?? 0;

            return (float) ($flight->base_price * (1 + ($markup / 100)));
        }

        if ($bookableType === Hotel::class || is_a($bookableType, Hotel::class, true)) {
            $hotel = Hotel::findOrFail($bookableId);
            $markup = $activeRules->filter(fn ($r) => in_array($r->service_type, ['hotel', 'all']))->max('markup_percent') ?? 0;

            return (float) ($hotel->base_price_per_night * (1 + ($markup / 100)));
        }

        if ($bookableType === Driver::class || is_a($bookableType, Driver::class, true)) {
            Driver::where('is_active', true)->findOrFail($bookableId);
            $markup = $activeRules->filter(fn ($r) => in_array($r->service_type, ['driver', 'all']))->max('markup_percent') ?? 0;

            return (float) (DomainDriver::DAILY_RATE_BASE * (1 + ($markup / 100)));
        }

        throw new \InvalidArgumentException('Tipe item yang dipesan tidak valid.');
    }

    public function remove(Request $request, $index)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$index])) {
            unset($cart[$index]);
            session()->put('cart', array_values($cart));
        }

        return back()->with('success', 'Item removed from cart.');
    }
}
