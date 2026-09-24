<?php

namespace App\Livewire\Admin\Lead;

use App\Domain\Lead\Models\Lead;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\User;
use App\Support\Branch\CurrentBranch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LeadForm extends Component
{
    /**
     * An id, not the Eloquent model — same convention as
     * PackageBuilder::$packageId. A nullable public Eloquent-model
     * property does not round-trip through Livewire's hydrate/dehydrate
     * cycle correctly (a second request rehydrates it as an empty model
     * instead of null), so the model is always re-fetched from this id.
     */
    #[Locked]
    public ?int $leadId = null;

    public string $name = '';

    public string $phone = '';

    public string $country = '';

    public string $locale = 'id';

    public string $source = 'manual';

    public string $status = 'new';

    public ?int $assigned_to = null;

    public string $lost_reason = '';

    /** datetime-local input format (Y-m-d\TH:i). Reminder is visual only — no email/notification sent. */
    public string $follow_up_at = '';

    public function updatedStatus(string $value): void
    {
        if ($value !== 'lost') {
            $this->lost_reason = '';
        }
    }

    public function mount(?Lead $lead = null): void
    {
        $this->authorize('viewAny', Lead::class);

        if ($lead?->exists) {
            $this->authorize('update', $lead);
            $this->leadId = $lead->id;
            $this->name = $lead->name;
            $this->phone = $lead->phone;
            $this->country = (string) $lead->country;
            $this->locale = $lead->locale;
            $this->source = $lead->source->value;
            $this->status = $lead->status->value;
            $this->assigned_to = $lead->assigned_to;
            $this->lost_reason = (string) $lead->lost_reason;
            $this->follow_up_at = $lead->follow_up_at?->format('Y-m-d\TH:i') ?? '';
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'locale' => ['required', 'in:en,id,ar'],
            'source' => ['required', 'in:manual,website'],
            'status' => ['required', 'in:new,contacted,qualified,quoted,won,lost'],
            'assigned_to' => [
                'nullable',
                Rule::in($this->assignableUsers()->pluck('id')),
            ],
            'lost_reason' => [$this->status === 'lost' ? 'required' : 'nullable', 'string', 'max:500'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        $data['follow_up_at'] = $data['follow_up_at'] ?: null;

        $lead = $this->leadId ? Lead::findOrFail($this->leadId) : null;

        if ($lead) {
            $this->authorize('update', $lead);
            $previousStatus = $lead->status->value;
            $lead->update($data);

            if ($previousStatus !== $data['status']) {
                $lead->activities()->create([
                    'user_id' => Auth::id(),
                    'type' => 'status_changed',
                    'note' => "{$previousStatus} -> {$data['status']}".($data['status'] === 'lost' && $data['lost_reason'] ? ": {$data['lost_reason']}" : ''),
                ]);
            }
        } else {
            $this->authorize('create', Lead::class);
            $data['branch_id'] = CurrentBranch::id();
            $lead = Lead::create($data);
            $lead->activities()->create([
                'user_id' => Auth::id(),
                'type' => 'created',
                'note' => null,
            ]);
            $this->leadId = $lead->id;
        }

        $this->dispatch('lead-saved');
        $this->redirect(route('admin.leads.edit', ['locale' => app()->getLocale(), 'lead' => $lead]));
    }

    public function render(): View
    {
        $lead = $this->leadId ? Lead::with('activities')->find($this->leadId) : null;

        return view('livewire.admin.lead.lead-form', [
            'lead' => $lead,
            'statuses' => LeadStatus::cases(),
            'sources' => LeadSource::cases(),
            'users' => $this->assignableUsers(),
        ]);
    }

    /** Only active staff of this branch who actually work leads (not customers or finance). */
    private function assignableUsers()
    {
        return User::permission('lead.manage')
            ->where('branch_id', CurrentBranch::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
