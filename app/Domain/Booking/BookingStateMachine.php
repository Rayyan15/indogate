<?php

namespace App\Domain\Booking;

use App\Domain\Booking\Exceptions\InvalidBookingTransitionException;
use App\Domain\Booking\Models\PackageBooking;
use App\Enums\BookingStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * PRD M7: the only legal way to change a booking's status. An unregistered
 * transition throws, it is never silently ignored. Every successful
 * transition writes one booking_status_histories row and one activity log
 * entry, both inside the same transaction as the status update.
 */
class BookingStateMachine
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'draft' => ['quoted', 'cancelled'],
        'quoted' => ['confirmed', 'expired', 'cancelled'],
        'confirmed' => ['partially_paid', 'cancelled'],
        'partially_paid' => ['paid', 'cancelled'],
        'paid' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    /** @return array<int, BookingStatus> */
    public function nextStatuses(BookingStatus $from): array
    {
        return array_map(
            fn (string $value) => BookingStatus::from($value),
            self::TRANSITIONS[$from->value] ?? [],
        );
    }

    public function transition(PackageBooking $booking, BookingStatus $to, ?string $reason = null, ?User $actor = null): void
    {
        $from = $booking->status;

        if ($to === BookingStatus::CANCELLED && ! $reason) {
            throw new InvalidArgumentException('A reason is required to cancel a booking.');
        }

        $allowed = self::TRANSITIONS[$from->value] ?? [];

        if (! in_array($to->value, $allowed, true)) {
            throw new InvalidBookingTransitionException(
                "Booking cannot transition from '{$from->value}' to '{$to->value}'."
            );
        }

        DB::transaction(function () use ($booking, $from, $to, $reason, $actor): void {
            $booking->update(['status' => $to]);

            $booking->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $actor?->id,
                'reason' => $reason,
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($booking)
                ->withProperties(['from' => $from->value, 'to' => $to->value])
                ->log("Booking status: {$from->value} -> {$to->value}");
        });
    }
}
