<?php

namespace App\Http\Controllers\Public;

use App\Domain\Lead\Models\Lead;
use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadCaptureController extends Controller
{
    /**
     * Public "form kontak" endpoint (PRD M6). No storefront exists yet
     * (that's M10) — this is a standalone functional form, not a designed
     * landing page. Spam protection: honeypot field ('website', must stay
     * empty) + throttle middleware on the route (PRD "proteksi spam").
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return back(); // honeypot tripped — silently drop, no error shown to the bot
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        $branch = Branch::where('is_active', true)->firstOrFail();

        Lead::create([
            'branch_id' => $branch->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'country' => $data['country'] ?? null,
            'locale' => app()->getLocale(),
            'source' => LeadSource::WEBSITE,
            'status' => 'new',
        ]);

        return back()->with('status', __('lead.public.thanks'));
    }
}
