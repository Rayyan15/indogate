<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Booking\Models\BookingGuest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BookingGuestDocumentController extends Controller
{
    /**
     * PRD M7 step 7-9: passport files live on the private disk, are only
     * reachable through a time-limited signed URL (see route definition),
     * and every access is logged — this app has no prior precedent for
     * logging *reads* (only state-change actions), so this is new.
     */
    public function download(BookingGuest $guest)
    {
        $this->authorize('view', $guest->booking);

        abort_unless($guest->passport_file && Storage::disk('local')->exists($guest->passport_file), 404);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($guest)
            ->log('Passport document accessed');

        return Storage::disk('local')->download($guest->passport_file);
    }
}
