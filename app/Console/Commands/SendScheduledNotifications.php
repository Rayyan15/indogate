<?php

namespace App\Console\Commands;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\Quotation;
use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\QuotationStatus;
use App\Notifications\DepartureWithoutDriver;
use App\Notifications\LeadFollowUpDue;
use App\Notifications\QuotationExpiring;
use App\Support\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Time-based staff notifications (push-notification-plan §2, §5 step 3). */
class SendScheduledNotifications extends Command
{
    protected $signature = 'notifications:scheduled {job : quotations|departures|follow-ups|prune}';

    protected $description = 'Send time-based staff notifications or prune old read ones';

    public function handle(): int
    {
        $count = match ($this->argument('job')) {
            'quotations' => $this->quotations(),
            'departures' => $this->departures(),
            'follow-ups' => $this->followUps(),
            'prune' => DB::table('notifications')->whereNotNull('read_at')->where('created_at', '<', now()->subDays(90))->delete(),
            default => -1,
        };

        if ($count < 0) {
            $this->error('Unknown job.');

            return self::INVALID;
        }

        $this->info("{$this->argument('job')}: {$count}");

        return self::SUCCESS;
    }

    private function quotations(): int
    {
        $quotations = Quotation::withoutGlobalScopes()->with('lead')
            ->whereIn('status', [QuotationStatus::DRAFT, QuotationStatus::SENT])
            ->whereBetween('valid_until', [now(), now()->addHours(48)])
            ->get();

        foreach ($quotations as $q) {
            $assignee = $q->lead?->assigned_to;
            Notify::send(
                new QuotationExpiring(['name' => (string) $q->lead?->name, 'date' => $q->valid_until->format('d M Y H:i')], Notify::url('admin.leads.edit', ['lead' => $q->lead_id]), $q->branch_id),
                $q->id, $assignee ? null : 'lead.manage', [$assignee],
            );
        }

        return $quotations->count();
    }

    /** Bookings departing tomorrow that still have no non-cancelled driver assignment. */
    public static function departuresQuery()
    {
        return PackageBooking::withoutGlobalScopes()
            ->whereDate('departure_date', now()->addDay()->toDateString())
            ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::EXPIRED, BookingStatus::COMPLETED])
            ->whereDoesntHave('assignments', fn ($q) => $q->withoutGlobalScopes()->where('status', '!=', DriverAssignment::STATUS_CANCELLED));
    }

    private function departures(): int
    {
        $bookings = self::departuresQuery()->get();

        foreach ($bookings as $b) {
            Notify::send(new DepartureWithoutDriver(['code' => $b->code], Notify::url('admin.package-bookings.show', ['packageBooking' => $b->id]), $b->branch_id), $b->id, 'driver.assign');
        }

        return $bookings->count();
    }

    private function followUps(): int
    {
        $leads = Lead::withoutGlobalScopes()
            ->whereNotNull('assigned_to')
            ->where('follow_up_at', '<=', now())
            ->whereNotIn('status', [LeadStatus::WON, LeadStatus::LOST])
            ->get();

        foreach ($leads as $lead) {
            Notify::send(new LeadFollowUpDue(['name' => $lead->name], Notify::url('admin.leads.edit', ['lead' => $lead->id]), $lead->branch_id), $lead->id, null, [$lead->assigned_to]);
        }

        return $leads->count();
    }
}
