<?php

namespace App\Domain\Security\Services;

use App\Domain\Booking\Models\BookingGuest;
use App\Domain\Booking\Models\PackageBooking;
use App\Enums\BookingStatus;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DataRetentionService
{
    /**
     * Purge identity documents for completed or cancelled bookings past the retention threshold.
     */
    public function purgeExpiredGuestDocuments(int $days = 90): int
    {
        $cutoffDate = now()->subDays($days)->toDateString();

        $expiredBookings = PackageBooking::query()
            ->withoutGlobalScopes()
            ->whereIn('status', [BookingStatus::COMPLETED->value, BookingStatus::CANCELLED->value])
            ->where(function ($q) use ($cutoffDate) {
                $q->where('departure_date', '<=', $cutoffDate)
                    ->orWhere(function ($q2) use ($cutoffDate) {
                        $q2->whereNull('departure_date')
                            ->whereDate('updated_at', '<=', $cutoffDate);
                    });
            })
            ->pluck('id');

        if ($expiredBookings->isEmpty()) {
            return 0;
        }

        $guests = BookingGuest::query()
            ->whereIn('booking_id', $expiredBookings)
            ->where(function ($q) {
                $q->whereNotNull('passport_file')
                    ->orWhereNotNull('passport_number');
            })
            ->get();

        $count = 0;

        foreach ($guests as $guest) {
            if ($guest->passport_file && Storage::disk('local')->exists($guest->passport_file)) {
                Storage::disk('local')->delete($guest->passport_file);
            }

            $guest->passport_file = null;
            $guest->passport_number = null;
            $guest->save();

            $count++;
        }

        if ($count > 0) {
            activity('security')
                ->causedBy(Auth::user())
                ->withProperties([
                    'days_threshold' => $days,
                    'cutoff_date' => $cutoffDate,
                    'purged_guests_count' => $count,
                ])
                ->log("Purged expired guest passport documents ({$count} records)");
        }

        return $count;
    }

    /**
     * Anonymize customer personal data upon request (UU PDP / GDPR Right to be Forgotten).
     */
    public function anonymizeCustomer(Customer $customer, ?string $reason = 'Customer request (UU PDP)'): void
    {
        $originalId = $customer->id;

        $customer->update([
            'full_name' => 'ANONYMIZED_'.$originalId,
            'passport_number' => null,
        ]);

        if ($customer->user) {
            $customer->user->update([
                'name' => 'ANONYMIZED_'.$customer->user_id,
                'email' => 'anonymized_'.$customer->user_id.'@deleted.local',
                'is_active' => false,
            ]);
        }

        $customer->delete();

        activity('security')
            ->causedBy(Auth::user())
            ->performedOn($customer)
            ->withProperties([
                'customer_id' => $originalId,
                'reason' => $reason,
            ])
            ->log('Customer personal data anonymized under UU PDP');
    }
}
