<?php

namespace App\Domain\Finance\Services;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Fx;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\Refund;
use Illuminate\Database\Eloquent\Collection;

class MarginReportService
{
    /**
     * Compute actual margin breakdown for a single package booking.
     *
     * @return array{
     *     booking: PackageBooking,
     *     gross_revenue_idr: int,
     *     channel_fees_idr: int,
     *     net_revenue_idr: int,
     *     vendor_costs_idr: int,
     *     actual_margin_idr: int,
     *     margin_percentage: float
     * }
     */
    public function computeBookingMargin(PackageBooking $booking): array
    {
        // 1. Gross revenue: verified payments IDR equivalent (or booking total in IDR if none yet verified)
        $verifiedPayments = $booking->payments()
            ->where('status', Payment::STATUS_VERIFIED)
            ->get();

        // Only verified money counts; pending/rejected never do (BF-10).
        // Completed refunds reduce revenue.
        $grossRevenueIdr = (int) $verifiedPayments->sum('idr_equivalent_minor')
            - (int) $booking->refunds()->where('status', Refund::STATUS_COMPLETED)->sum('idr_equivalent_minor');

        if ($verifiedPayments->isEmpty()) {
            // Expected revenue: booking total at the quotation's locked rate.
            $grossRevenueIdr = Fx::toIdrMinor((int) $booking->total_minor, $booking->currency, $booking->lockedRate());
        }

        // 2. Channel fees in IDR (fee is in the payment's minor units, BF-09)
        $channelFeesIdr = (int) $verifiedPayments->sum(
            fn ($payment) => Fx::toIdrMinor((int) $payment->channel_fee_minor, $payment->currency, (float) $payment->fx_rate)
        );

        // 3. Vendor costs: actual recorded vendor payments, or fall back to quotation item cost
        $actualVendorPaymentsIdr = (int) $booking->vendorPayments()->sum('idr_equivalent_minor');
        if ($actualVendorPaymentsIdr > 0) {
            $vendorCostsIdr = $actualVendorPaymentsIdr;
        } else {
            // Estimated vendor cost from quotation items
            $vendorCostsIdr = (int) ($booking->quotation?->items->sum('cost_minor') ?? 0);
        }

        // 4. Net revenue & margin
        $netRevenueIdr = max(0, $grossRevenueIdr - $channelFeesIdr);
        $actualMarginIdr = $netRevenueIdr - $vendorCostsIdr;

        $marginPercentage = $grossRevenueIdr > 0
            ? round(($actualMarginIdr / $grossRevenueIdr) * 100, 2)
            : 0.0;

        return [
            'booking' => $booking,
            'gross_revenue_idr' => $grossRevenueIdr,
            'channel_fees_idr' => $channelFeesIdr,
            'net_revenue_idr' => $netRevenueIdr,
            'vendor_costs_idr' => $vendorCostsIdr,
            'actual_margin_idr' => $actualMarginIdr,
            'margin_percentage' => $marginPercentage,
        ];
    }

    /**
     * Compute summary across a collection of bookings.
     */
    public function computeSummary(Collection $bookings): array
    {
        $totalGross = 0;
        $totalFees = 0;
        $totalNet = 0;
        $totalVendor = 0;
        $totalMargin = 0;

        foreach ($bookings as $booking) {
            $data = $this->computeBookingMargin($booking);
            $totalGross += $data['gross_revenue_idr'];
            $totalFees += $data['channel_fees_idr'];
            $totalNet += $data['net_revenue_idr'];
            $totalVendor += $data['vendor_costs_idr'];
            $totalMargin += $data['actual_margin_idr'];
        }

        $overallPercentage = $totalGross > 0
            ? round(($totalMargin / $totalGross) * 100, 2)
            : 0.0;

        return [
            'total_gross_idr' => $totalGross,
            'total_fees_idr' => $totalFees,
            'total_net_idr' => $totalNet,
            'total_vendor_idr' => $totalVendor,
            'total_margin_idr' => $totalMargin,
            'overall_margin_percentage' => $overallPercentage,
        ];
    }

    /**
     * Alias for computeSummary.
     *
     * @param  Collection<int, PackageBooking>|\Illuminate\Support\Collection<int, PackageBooking>  $bookings
     * @return array<string, mixed>
     */
    public function computeOverallSummary($bookings): array
    {
        return $this->computeSummary($bookings);
    }
}
