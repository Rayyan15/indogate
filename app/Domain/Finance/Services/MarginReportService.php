<?php

namespace App\Domain\Finance\Services;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Models\Payment;
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

        $grossRevenueIdr = (int) $verifiedPayments->sum('idr_equivalent_minor');
        if ($grossRevenueIdr === 0 && $booking->payments()->exists()) {
            $grossRevenueIdr = (int) $booking->payments()->sum('idr_equivalent_minor');
        } elseif ($grossRevenueIdr === 0) {
            // Fall back to booking total converted to IDR
            $rate = (float) ($booking->quotation?->locked_rate ?? 1.0);
            $grossRevenueIdr = (int) round($booking->total_minor * $rate);
        }

        // 2. Channel fees in IDR
        $channelFeesIdr = 0;
        foreach ($verifiedPayments as $payment) {
            $rate = (float) $payment->fx_rate;
            $feeInIdr = (int) round($payment->channel_fee_minor * ($rate > 0 ? $rate : 1.0));
            $channelFeesIdr += $feeInIdr;
        }

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
