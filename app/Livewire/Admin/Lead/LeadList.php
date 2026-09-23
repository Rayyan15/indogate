<?php

namespace App\Livewire\Admin\Lead;

use App\Domain\Lead\Models\Lead;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Livewire\Concerns\Sortable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class LeadList extends Component
{
    use Sortable, WithPagination;

    private const SORTABLE_FIELDS = ['name', 'status', 'created_at'];

    /** SSR default only — real preference read from localStorage client-side, same pattern as PackageList::$viewMode. */
    public string $viewMode = 'list';

    public string $search = '';

    public string $statusFilter = '';

    public string $sourceFilter = '';

    public bool $dueOnly = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Lead::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDueOnly(): void
    {
        $this->resetPage();
    }

    #[On('lead-saved')]
    public function refresh(): void {}

    private function filteredQuery()
    {
        return Lead::query()
            ->with(['assignee:id,name', 'quotations:id,lead_id,status'])
            ->when($this->search, function ($q) {
                $escaped = addcslashes($this->search, '%_\\');
                $q->where(function ($sub) use ($escaped) {
                    $sub->where('name', 'like', "%{$escaped}%")
                        ->orWhere('phone', 'like', "%{$escaped}%");
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->sourceFilter, fn ($q) => $q->where('source', $this->sourceFilter))
            ->when($this->dueOnly, fn ($q) => $q->whereNotNull('follow_up_at')
                ->where('follow_up_at', '<=', now())
                ->whereNotIn('status', ['won', 'lost']));
    }

    public function render(): View
    {
        $leads = $this->applySort($this->filteredQuery(), self::SORTABLE_FIELDS, defaultField: 'created_at')
            ->paginate(15);

        // Kanban shows both views' filtered result in one response (view
        // toggle is pure client-side Alpine, no round-trip) — capped rather
        // than paginated, a lead board this size fits on screen at once.
        $kanbanLeads = $this->filteredQuery()->latest('created_at')->limit(300)->get()
            ->groupBy(fn (Lead $lead) => $lead->status->value);

        return view('livewire.admin.lead.lead-list', [
            'leads' => $leads,
            'kanbanLeads' => $kanbanLeads,
            'statuses' => LeadStatus::cases(),
            'sources' => LeadSource::cases(),
        ]);
    }
}
