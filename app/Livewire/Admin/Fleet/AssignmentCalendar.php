<?php

namespace App\Livewire\Admin\Fleet;

use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Fleet\Services\AssignmentService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithPagination;

class AssignmentCalendar extends Component
{
    use WithPagination;

    public string $selectedDate = '';

    public string $statusFilter = '';

    public string $search = '';

    public bool $showCancelModal = false;

    public ?int $cancellingAssignmentId = null;

    public string $cancel_reason = '';

    protected $queryString = [
        'selectedDate' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('driver.assign'), 403);
        if ($this->selectedDate === '') {
            $this->selectedDate = now()->toDateString();
        }
    }

    public function updatingSelectedDate(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCancelModal(int $assignmentId): void
    {
        $this->cancellingAssignmentId = $assignmentId;
        $this->cancel_reason = '';
        $this->showCancelModal = true;
    }

    public function cancelAssignment(): void
    {
        $this->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ]);

        $assignment = DriverAssignment::findOrFail($this->cancellingAssignmentId);
        $this->authorize('update', $assignment);

        (new AssignmentService)->cancel($assignment, $this->cancel_reason);

        $this->showCancelModal = false;
        $this->reset(['cancellingAssignmentId', 'cancel_reason']);
    }

    public function dutyLetterUrl(int $assignmentId): string
    {
        return URL::temporarySignedRoute(
            'admin.fleet.assignments.duty-letter',
            now()->addMinutes(60),
            ['assignment' => $assignmentId]
        );
    }

    public function render(): View
    {
        $date = Carbon::parse($this->selectedDate)->toDateString();

        $query = DriverAssignment::with(['driver', 'vehicle', 'booking.quotation.lead', 'branch'])
            ->where(function ($q) use ($date) {
                // Active on the selected date
                $q->whereDate('date_from', '<=', $date)
                    ->whereDate('date_to', '>=', $date);
            })
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q) {
                $escaped = addcslashes($this->search, '%_\\');
                $q->where(function ($sq) use ($escaped) {
                    $sq->whereHas('driver', fn ($dq) => $dq->where('name', 'like', "%{$escaped}%"))
                        ->orWhereHas('vehicle', fn ($vq) => $vq->where('plate', 'like', "%{$escaped}%"))
                        ->orWhereHas('booking', fn ($bq) => $bq->where('code', 'like', "%{$escaped}%"));
                });
            })
            ->orderBy('date_from');

        return view('livewire.admin.fleet.assignment-calendar', [
            'assignments' => $query->paginate(15),
            'targetDate' => Carbon::parse($this->selectedDate),
        ]);
    }
}
