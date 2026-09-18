<?php

namespace App\Domain\Booking;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Lead\Models\Quotation;
use App\Enums\BookingStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A quotation the guest already approved (outside the system — WhatsApp,
 * phone) is converted straight into a `confirmed` booking, not `draft`.
 * `Quotation.status` (M6) already represents the pre-booking lifecycle, so
 * this doesn't replay draft/quoted here — it writes a single explicit
 * history row (draft -> confirmed) so "every transition produces a history
 * row" (PRD) still holds for the booking's own row, without forcing a fake
 * multi-step replay through BookingStateMachine.
 */
class ConvertQuotationToBooking
{
    public function convert(Quotation $quotation, string $departureDate, ?string $returnDate, ?User $actor = null): PackageBooking
    {
        return DB::transaction(function () use ($quotation, $departureDate, $returnDate, $actor) {
            $booking = PackageBooking::create([
                'branch_id' => $quotation->branch_id,
                'quotation_id' => $quotation->id,
                'code' => $this->uniqueCode(),
                'status' => BookingStatus::CONFIRMED,
                'departure_date' => $departureDate,
                'return_date' => $returnDate,
                'total_minor' => $quotation->items->sum('total_minor'),
                'currency' => $quotation->currency,
            ]);

            $booking->statusHistories()->create([
                'from_status' => BookingStatus::DRAFT,
                'to_status' => BookingStatus::CONFIRMED,
                'user_id' => $actor?->id,
                'reason' => 'Dikonversi dari penawaran',
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($booking)
                ->log('Booking created from quotation');

            return $booking;
        });
    }

    private function uniqueCode(): string
    {
        do {
            $code = 'BK-'.Str::upper(Str::random(8));
        } while (PackageBooking::withoutGlobalScopes()->where('code', $code)->exists());

        return $code;
    }
}
