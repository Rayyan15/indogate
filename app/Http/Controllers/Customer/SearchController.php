<?php

namespace App\Http\Controllers\Customer;

use App\Domain\Fleet\Models\Driver as DomainDriver;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\FlightRoute;
use App\Models\Hotel;
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
            if ($request->filled('origin')) {
                $flights->where('origin', 'like', '%'.$request->origin.'%');
            }
            if ($request->filled('destination')) {
                $flights->where('destination', 'like', '%'.$request->destination.'%');
            }
        } elseif ($type === 'hotels') {
            if ($request->filled('location')) {
                $hotels->where('location', 'like', '%'.$request->location.'%');
            }
        }

        $flightsResult = $type === 'flights' ? $flights->paginate(12)->withQueryString() : null;
        if ($flightsResult) {
            $flightsResult->getCollection()->transform(function ($f) {
                $f->base_price = CartController::applyMarkup((float) $f->base_price, 'flight', $f->branch_id);

                return $f;
            });
        }

        $hotelsResult = $type === 'hotels' ? $hotels->paginate(12)->withQueryString() : null;
        if ($hotelsResult) {
            $hotelsResult->getCollection()->transform(function ($h) {
                $h->base_price_per_night = CartController::applyMarkup((float) $h->base_price_per_night, 'hotel', $h->branch_id);

                return $h;
            });
        }

        $driversResult = $type === 'drivers' ? $drivers->paginate(12)->withQueryString() : null;
        if ($driversResult) {
            $driversResult->getCollection()->transform(function ($d) {
                $d->display_price = CartController::applyMarkup((float) DomainDriver::DAILY_RATE_BASE, 'driver', $d->branch_id);

                return $d;
            });
        }

        return view('customer.search.index', [
            'type' => $type,
            'flights' => $flightsResult,
            'hotels' => $hotelsResult,
            'drivers' => $driversResult,
        ]);
    }
}
