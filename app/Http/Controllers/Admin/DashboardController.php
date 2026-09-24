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

        // Each desk role lands on its own work surface instead of the KPI overview.
        if ($user->can('desk.cs') && ! $user->can('desk.admin')) {
            return redirect()->route('admin.desk');
        }

        if ($user->can('desk.admin') && ! $user->can('pricing.manage')) {
            return redirect()->route('admin.control');
        }

        return view('admin.dashboard');
    }
}
