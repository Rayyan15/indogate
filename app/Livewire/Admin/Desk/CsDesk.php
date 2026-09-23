<?php

namespace App\Livewire\Admin\Desk;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\Quotation;
use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\QuotationStatus;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Front-line work queue for CS: what needs a reply or an action today.
 * Every query goes through BranchScope, so staff only see their branch.
 */
class CsDesk extends Component
{
    private const LIST_LIMIT = 5;

    private const SEARCH_LIMIT = 8;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->canAny(['lead.manage', 'booking.manage']), 403);
    }

    public function render(): View
    {
        $user = Auth::user();
        $canLeads = $user->can('lead.manage');
        $canBookings = $user->can('booking.manage');

        return view('livewire.admin.desk.cs-desk', [
            'canLeads' => $canLeads,
            'canBookings' => $canBookings,
            'results' => $this->searchResults($canLeads, $canBookings),
            'leads' => $canLeads ? $this->followUpLeads() : null,
            'quotations' => $canLeads ? $this->waitingQuotations() : null,
            'awaitingPayment' => $canBookings ? $this->awaitingPayment() : null,
            'departing' => $canBookings ? $this->departingSoon() : null,
            'onlineOrders' => $canBookings
                ? Booking::whereIn('status', [Booking::STATUS_PENDING_PAYMENT, Booking::STATUS_PAYMENT_SUBMITTED])->count()
                : null,
        ]);
    }

    /** @return array{leads: Collection, bookings: Collection}|null */
    private function searchResults(bool $canLeads, bool $canBookings): ?array
    {
        $term = trim($this->search);

        if (mb_strlen($term) < 2) {
            return null;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return [
            'leads' => $canLeads
                ? Lead::where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('phone', 'like', $like))
                    ->latest()->limit(self::SEARCH_LIMIT)->get()
                : collect(),
            'bookings' => $canBookings
                ? PackageBooking::with('quotation.lead')
                    ->where(fn (Builder $q) => $q->where('code', 'like', $like)
                        ->orWhereHas('quotation.lead', fn (Builder $l) => $l->where('name', 'like', $like)->orWhere('phone', 'like', $like))
                        ->orWhereHas('guests', fn (Builder $g) => $g->where('name', 'like', $like)))
                    ->latest()->limit(self::SEARCH_LIMIT)->get()
                : collect(),
        ];
    }

    /** New leads nobody has contacted yet, plus open leads whose follow-up date has passed. */
    private function followUpLeads(): array
    {
        $query = Lead::where(fn (Builder $q) => $q
            ->where('status', LeadStatus::NEW)
            ->orWhere(fn (Builder $due) => $due
                ->where('follow_up_at', '<=', now())
                ->whereNotIn('status', [LeadStatus::WON, LeadStatus::LOST])));

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderByRaw('follow_up_at is null')->orderBy('follow_up_at')->oldest()
                ->limit(self::LIST_LIMIT)->get(),
        ];
    }

    private function waitingQuotations(): array
    {
        $query = Quotation::with(['lead', 'package'])
            // Nothing flips quotations to SENT yet, so "open" = draft or sent, still valid, not converted.
            ->whereIn('status', [QuotationStatus::DRAFT, QuotationStatus::SENT])
            ->where('valid_until', '>', now())
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('package_bookings')->whereColumn('package_bookings.quotation_id', 'quotations.id'));

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderBy('valid_until')->limit(self::LIST_LIMIT)->get(),
        ];
    }

    private function awaitingPayment(): array
    {
        $query = PackageBooking::with(['quotation.lead', 'payments', 'refunds'])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PARTIALLY_PAID]);

        // ponytail: balance is computed in PHP (FX-aware), fine at branch scale; move to SQL if open bookings reach thousands.
        $open = $query->orderBy('departure_date')->get()->filter(fn (PackageBooking $b) => $b->remainingBalanceMinor() > 0);

        return [
            'count' => $open->count(),
            'items' => $open->take(self::LIST_LIMIT)->values(),
        ];
    }

    private function departingSoon(): array
    {
        $query = PackageBooking::with('quotation.lead')
            ->whereDate('departure_date', '>=', today())
            ->whereDate('departure_date', '<=', today()->addDay())
            ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::EXPIRED, BookingStatus::DRAFT]);

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderBy('departure_date')->limit(self::LIST_LIMIT)->get(),
        ];
    }
}
