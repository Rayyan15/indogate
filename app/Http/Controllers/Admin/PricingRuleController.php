<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use Illuminate\Http\Request;

class PricingRuleController extends Controller
{
    public function index()
    {
        $rules = PricingRule::latest()->paginate(10);

        return view('admin.pricing.index', compact('rules'));
    }

    public function create()
    {
        return view('admin.pricing.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|string',
            'season_start' => 'required|date',
            'season_end' => 'required|date|after_or_equal:season_start',
            'markup_percent' => 'required|numeric|min:0|max:500',
            'tier' => 'nullable|string',
        ]);

        PricingRule::create($validated);

        return redirect()->route('admin.pricing.index')->with('success', 'Pricing rule created successfully.');
    }

    public function edit(PricingRule $pricing_rule)
    {
        return view('admin.pricing.edit', ['rule' => $pricing_rule]);
    }

    public function update(Request $request, PricingRule $pricing_rule)
    {
        $validated = $request->validate([
            'service_type' => 'required|string',
            'season_start' => 'required|date',
            'season_end' => 'required|date|after_or_equal:season_start',
            'markup_percent' => 'required|numeric|min:0|max:500',
            'tier' => 'nullable|string',
        ]);

        $pricing_rule->update($validated);

        return redirect()->route('admin.pricing.index')->with('success', 'Pricing rule updated successfully.');
    }

    public function destroy(PricingRule $pricing_rule)
    {
        $pricing_rule->delete();

        return redirect()->route('admin.pricing.index')->with('success', 'Pricing rule deleted successfully.');
    }
}
