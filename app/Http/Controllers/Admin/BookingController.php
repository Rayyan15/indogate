<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Driver;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::with('customer')->latest()->paginate(10);

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        $booking->load('customer', 'items', 'payments');
        $drivers = Driver::where('branch_id', $booking->branch_id)->where('is_active', true)->get();

        return view('admin.bookings.show', compact('booking', 'drivers'));
    }

    public function assignDriver(Request $request, Booking $booking)
    {
        $this->authorize('assignDriver', $booking);

        $request->validate([
            'driver_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('drivers', 'id')
                    ->where('branch_id', $booking->branch_id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
        ]);
        $booking->update(['driver_id' => $request->driver_id]);

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Driver berhasil ditugaskan.');
    }

    // Read-only for the rest
    public function create() {}

    public function store(Request $request) {}

    public function edit(Booking $booking) {}

    public function update(Request $request, Booking $booking) {}

    public function destroy(Booking $booking) {}
}
