<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        // Front-line staff (CS) land on their work queue instead of the KPI overview.
        if (! $user->can('report.view') && $user->canAny(['lead.manage', 'booking.manage'])) {
            return redirect()->route('admin.desk');
        }

        return view('admin.dashboard');
    }
}
