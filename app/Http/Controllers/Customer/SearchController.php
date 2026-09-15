<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\FlightRoute;
use App\Models\Hotel;
use App\Models\Driver;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'flights');

        $flights = FlightRoute::query();
        $hotels = Hotel::query();
        $drivers = Driver::where('is_active', true);

        if ($type === 'flights') {
            if ($request->filled('origin')) $flights->where('origin', 'like', '%' . $request->origin . '%');
            if ($request->filled('destination')) $flights->where('destination', 'like', '%' . $request->destination . '%');
        } elseif ($type === 'hotels') {
            if ($request->filled('location')) $hotels->where('location', 'like', '%' . $request->location . '%');
        }

        $activeRules = \App\Models\PricingRule::where('season_start', '<=', now())
            ->where('season_end', '>=', now())
            ->get();

        $flightRules = $activeRules->filter(fn($r) => in_array($r->service_type, ['flight', 'all']))->max('markup_percent') ?? 0;
        $hotelRules = $activeRules->filter(fn($r) => in_array($r->service_type, ['hotel', 'all']))->max('markup_percent') ?? 0;
        $driverRules = $activeRules->filter(fn($r) => in_array($r->service_type, ['driver', 'all']))->max('markup_percent') ?? 0;

        $flightsResult = $type === 'flights' ? $flights->paginate(12) : null;
        if ($flightsResult) {
            $flightsResult->getCollection()->transform(function($f) use ($flightRules) {
                $f->base_price = $f->base_price * (1 + ($flightRules / 100));
                return $f;
            });
        }

        $hotelsResult = $type === 'hotels' ? $hotels->paginate(12) : null;
        if ($hotelsResult) {
            $hotelsResult->getCollection()->transform(function($h) use ($hotelRules) {
                $h->base_price_per_night = $h->base_price_per_night * (1 + ($hotelRules / 100));
                return $h;
            });
        }

        $driversResult = $type === 'drivers' ? $drivers->paginate(12) : null;
        // drivers price is hardcoded in the view to 500,000. Let's pass the markup to the view for drivers.
        $driverPrice = 500000 * (1 + ($driverRules / 100));

        return view('customer.search.index', [
            'type' => $type,
            'flights' => $flightsResult,
            'hotels' => $hotelsResult,
            'drivers' => $driversResult,
            'driverPrice' => $driverPrice,
        ]);
    }
}
