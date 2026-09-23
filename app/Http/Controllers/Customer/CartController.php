<?php

namespace App\Http\Controllers\Customer;

use App\Domain\Fleet\Models\Driver as DomainDriver;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\FlightRoute;
use App\Models\Hotel;
use App\Models\PricingRule;
use App\Support\Branch\BranchScope;
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
        return self::resolveItem($bookableType, $bookableId)['price'];
    }

    /**
     * Server-side price + owning branch of a cart item. The storefront is
     * public, so items are looked up across branches, but only the owning
     * branch's pricing rules apply (not the max markup of every branch).
     *
     * @return array{price: float, branch_id: int|null}
     */
    public static function resolveItem(string $bookableType, int $bookableId): array
    {
        [$serviceType, $model] = match (true) {
            is_a($bookableType, FlightRoute::class, true) => ['flight', FlightRoute::withoutGlobalScope(BranchScope::class)->findOrFail($bookableId)],
            is_a($bookableType, Hotel::class, true) => ['hotel', Hotel::withoutGlobalScope(BranchScope::class)->findOrFail($bookableId)],
            is_a($bookableType, Driver::class, true) => ['driver', Driver::withoutGlobalScope(BranchScope::class)->where('is_active', true)->findOrFail($bookableId)],
            default => throw new \InvalidArgumentException('Tipe item yang dipesan tidak valid.'),
        };

        $base = match ($serviceType) {
            'flight' => $model->base_price,
            'hotel' => $model->base_price_per_night,
            'driver' => DomainDriver::DAILY_RATE_BASE,
        };

        $today = today()->toDateString();
        $markup = PricingRule::withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $model->branch_id)
            ->whereIn('service_type', [$serviceType, 'all'])
            ->where('season_start', '<=', $today)
            ->where('season_end', '>=', $today)
            ->max('markup_percent') ?? 0;

        return [
            'price' => round((float) $base * (1 + ($markup / 100)), 2),
            'branch_id' => $model->branch_id,
        ];
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
