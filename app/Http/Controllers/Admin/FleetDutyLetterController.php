<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Fleet\Models\DriverAssignment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FleetDutyLetterController extends Controller
{
    public function show(DriverAssignment $assignment)
    {
        $this->authorize('view', $assignment);

        if (function_exists('activity')) {
            activity('fleet')
                ->performedOn($assignment)
                ->causedBy(Auth::user())
                ->withProperties([
                    'assignment_id' => $assignment->id,
                    'booking_code' => $assignment->booking?->code,
                    'driver_name' => $assignment->driver?->name,
                ])
                ->log("Surat tugas dicetak untuk booking {$assignment->booking?->code}");
        }

        $assignment->load(['branch', 'booking.guests', 'driver', 'vehicle']);

        return view('pdf.duty-letter', [
            'assignment' => $assignment,
            'locale' => app()->getLocale(),
        ]);
    }
}
