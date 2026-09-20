<?php

namespace App\Livewire\Admin\Security;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class AuditLogList extends Component
{
    use WithPagination;

    public string $logName = '';

    public ?int $causerId = null;

    public string $search = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $selectedLogId = null;

    public ?array $selectedProperties = null;

    public string $selectedDescription = '';

    protected $queryString = [
        'logName' => ['except' => ''],
        'causerId' => ['except' => null],
        'search' => ['except' => ''],
        'dateFrom' => ['except' => null],
        'dateTo' => ['except' => null],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLogName(): void
    {
        $this->resetPage();
    }

    public function updatingCauserId(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function viewDetails(int $id): void
    {
        $log = Activity::findOrFail($id);
        $this->selectedLogId = $log->id;
        $this->selectedDescription = $log->description;
        $this->selectedProperties = $log->properties?->toArray() ?? [];
    }

    public function closeDetails(): void
    {
        $this->selectedLogId = null;
        $this->selectedProperties = null;
        $this->selectedDescription = '';
    }

    public function render(): View
    {
        abort_unless(Auth::user()?->can('activitylog.view'), 403);

        $sanitizedSearch = str_replace(['%', '_'], ['\\%', '\\_'], trim($this->search));

        $logs = Activity::query()
            ->with(['causer'])
            ->when($this->logName !== '', fn ($q) => $q->where('log_name', $this->logName))
            ->when($this->causerId !== null && $this->causerId > 0, fn ($q) => $q->where('causer_id', $this->causerId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($sanitizedSearch !== '', function ($q) use ($sanitizedSearch) {
                $q->where(function ($sub) use ($sanitizedSearch) {
                    $sub->where('description', 'like', "%{$sanitizedSearch}%")
                        ->orWhere('properties', 'like', "%{$sanitizedSearch}%")
                        ->orWhere('subject_type', 'like', "%{$sanitizedSearch}%");
                });
            })
            ->latest('id')
            ->paginate(20);

        $availableLogNames = Activity::query()
            ->select('log_name')
            ->distinct()
            ->whereNotNull('log_name')
            ->pluck('log_name')
            ->sort()
            ->values();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.admin.security.audit-log-list', [
            'logs' => $logs,
            'availableLogNames' => $availableLogNames,
            'users' => $users,
        ]);
    }
}
