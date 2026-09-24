<?php

namespace App\Http\Controllers\Public;

use App\Domain\Lead\Models\Lead;
use App\Domain\Packaging\Models\Package;
use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Notifications\NewWebsiteLead;
use App\Support\Notify;
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
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'pax' => ['nullable', 'integer', 'min:1', 'max:100'],
            'travel_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // A lead about a specific package belongs to that package's branch (bug-review 05 M-06).
        $packageBranchId = isset($data['package_id'])
            ? Package::withoutGlobalScopes()->whereKey($data['package_id'])->value('branch_id')
            : null;
        $branch = Branch::where('is_active', true)
            ->when($packageBranchId, fn ($q) => $q->whereKey($packageBranchId))
            ->first() ?? Branch::where('is_active', true)->firstOrFail();

        $lead = Lead::create([
            'branch_id' => $branch->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'country' => $data['country'] ?? null,
            'locale' => app()->getLocale(),
            'source' => LeadSource::WEBSITE,
            'status' => 'new',
        ]);

        // If package or travel inquiry details are provided, record an activity note
        $noteParts = [];
        if (! empty($data['package_id'])) {
            $package = Package::find($data['package_id']);
            if ($package) {
                $noteParts[] = "Paket: {$package->name}";
            }
        }
        if (! empty($data['pax'])) {
            $noteParts[] = "Jumlah Pax: {$data['pax']}";
        }
        if (! empty($data['travel_date'])) {
            $noteParts[] = "Perkiraan Tanggal: {$data['travel_date']}";
        }
        if (! empty($data['notes'])) {
            $noteParts[] = "Catatan: {$data['notes']}";
        }

        if (! empty($noteParts)) {
            $lead->activities()->create([
                'type' => 'note',
                'note' => implode(' | ', $noteParts),
            ]);
        }

        Notify::send(new NewWebsiteLead(['name' => $lead->name], Notify::url('admin.leads.edit', ['lead' => $lead->id]), $lead->branch_id), $lead->id, 'lead.manage');

        return back()->with('status', __('storefront.inquiry_success'));
    }
}
