<?php

namespace App\Http\Controllers\Public;

use App\Domain\Packaging\Models\Package;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\Storefront\StorefrontCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    /**
     * Storefront Landing Page (PRD M10).
     */
    public function home($locale = null): View
    {
        $featuredPackages = Package::published()
            ->featured()
            ->with(['branch'])
            ->take(3)
            ->get();

        // If fewer than 3 featured packages, fill with latest published packages
        if ($featuredPackages->count() < 3) {
            $existingIds = $featuredPackages->pluck('id')->toArray();
            $morePackages = Package::published()
                ->whereNotIn('id', $existingIds)
                ->with(['branch'])
                ->latest()
                ->take(3 - $featuredPackages->count())
                ->get();
            $featuredPackages = $featuredPackages->concat($morePackages);
        }

        $branches = Branch::where('is_active', true)->get();

        return view('public.home', compact('featuredPackages', 'branches'));
    }

    /**
     * Storefront Package Catalog.
     */
    public function catalog($locale = null): View
    {
        $branches = Branch::where('is_active', true)->get();

        return view('public.catalog', compact('branches'));
    }

    /**
     * Storefront Package Detail.
     * Enforces published check; non-published package must return 404.
     */
    public function show($package): View
    {
        $packageId = $package instanceof Package ? $package->id : (int) $package;

        $packageModel = Package::published()
            ->with(['branch', 'days', 'items.inventoryItem'])
            ->findOrFail($packageId);

        return view('public.package-detail', ['package' => $packageModel]);
    }

    /**
     * Switch Storefront Currency.
     */
    public function switchCurrency(Request $request, $locale = null): RedirectResponse
    {
        $currency = $request->input('currency');
        if (StorefrontCurrency::isSupported($currency)) {
            StorefrontCurrency::set($currency);
        }

        return back();
    }
}
