<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlightRoute;
use Illuminate\Http\Request;

class FlightRouteController extends Controller
{
    public function index()
    {
        $flights = FlightRoute::paginate(10);
        return view('admin.flights.index', compact('flights'));
    }

    public function create()
    {
        return view('admin.flights.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'airline'       => 'required|string|max:255',
            'origin'        => 'required|string|max:255',
            'destination'   => 'required|string|max:255',
            'departure_at'  => 'required|date',
            'base_price'    => 'required|integer|min:0',
            'seat_quota'    => 'required|integer|min:0',
        ]);

        FlightRoute::create($validated);

        return redirect()->route('admin.flights.index')->with('success', 'Flight route created successfully.');
    }

    public function show(FlightRoute $flight)
    {
        //
    }

    public function edit(FlightRoute $flight)
    {
        return view('admin.flights.edit', compact('flight'));
    }

    public function update(Request $request, FlightRoute $flight)
    {
        $validated = $request->validate([
            'airline'       => 'required|string|max:255',
            'origin'        => 'required|string|max:255',
            'destination'   => 'required|string|max:255',
            'departure_at'  => 'required|date',
            'base_price'    => 'required|integer|min:0',
            'seat_quota'    => 'required|integer|min:0',
        ]);

        $flight->update($validated);

        return redirect()->route('admin.flights.index')->with('success', 'Flight route updated successfully.');
    }

    public function destroy(FlightRoute $flight)
    {
        $flight->delete();
        return redirect()->route('admin.flights.index')->with('success', 'Flight route deleted successfully.');
    }
}
