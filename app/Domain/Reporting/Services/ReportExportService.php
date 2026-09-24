<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Services\MarginReportService;
use App\Domain\Lead\Models\Lead;
use Illuminate\Support\Collection;

class ReportExportService
{
    /**
     * Sales & Margin CSV (UTF-8 BOM) as a string.
     *
     * @param  iterable<int, PackageBooking>  $bookings
     */
    public function exportSalesMargin(iterable $bookings): string
    {
        return $this->toString(fn ($h) => $this->writeSalesMargin($h, $bookings));
    }

    /** @param  iterable<int, PackageBooking>  $bookings */
    public function streamSalesMargin(iterable $bookings): void
    {
        $this->toOutput(fn ($h) => $this->writeSalesMargin($h, $bookings));
    }

    /**
     * Lead Conversion CSV (UTF-8 BOM) as a string.
     *
     * @param  iterable<int, Lead>  $leads
     */
    public function exportLeadConversion(iterable $leads): string
    {
        return $this->toString(fn ($h) => $this->writeLeadConversion($h, $leads));
    }

    /** @param  iterable<int, Lead>  $leads */
    public function streamLeadConversion(iterable $leads): void
    {
        $this->toOutput(fn ($h) => $this->writeLeadConversion($h, $leads));
    }

    /**
     * Operational Package Bookings CSV (UTF-8 BOM) as a string.
     * Callers should eager load branch, quotation.lead, quotation.package, payments.
     *
     * @param  iterable<int, PackageBooking>  $bookings
     */
    public function exportBookings(iterable $bookings): string
    {
        return $this->toString(fn ($h) => $this->writeBookings($h, $bookings));
    }

    /** @param  iterable<int, PackageBooking>  $bookings */
    public function streamBookings(iterable $bookings): void
    {
        $this->toOutput(fn ($h) => $this->writeBookings($h, $bookings));
    }

    private function writeSalesMargin($handle, iterable $bookings): void
    {
        // UTF-8 BOM for Excel
        fwrite($handle, "\xEF\xBB\xBF");

        $this->row($handle, [
            'Kode Pemesanan',
            'Cabang',
            'Nama Tamu',
            'Paket Wisata',
            'Tanggal Berangkat',
            'Tanggal Pulang',
            'Status',
            'Pendapatan Kotor (IDR)',
            'Biaya Kanal MDR (IDR)',
            'Pendapatan Bersih (IDR)',
            'Biaya Modal Vendor (IDR)',
            'Margin Sesungguhnya (IDR)',
            'Persentase Margin (%)',
        ]);

        $marginService = new MarginReportService;

        foreach ($bookings as $booking) {
            $calc = $marginService->computeBookingMargin($booking);
            $b = $calc['booking'];

            $this->row($handle, [
                $b->code,
                $b->branch?->name ?? "Cabang #{$b->branch_id}",
                $b->quotation?->lead?->name ?? '-',
                $b->quotation?->package?->name ?? '-',
                $b->departure_date ? $b->departure_date->toDateString() : '-',
                $b->return_date ? $b->return_date->toDateString() : '-',
                $b->status?->value ?? (string) $b->status,
                $calc['gross_revenue_idr'],
                $calc['channel_fees_idr'],
                $calc['net_revenue_idr'],
                $calc['vendor_costs_idr'],
                $calc['actual_margin_idr'],
                number_format($calc['margin_percentage'], 1).'%',
            ]);
        }
    }

    private function writeLeadConversion($handle, iterable $leads): void
    {
        fwrite($handle, "\xEF\xBB\xBF");

        $this->row($handle, [
            'ID Prospek',
            'Tanggal Masuk',
            'Cabang',
            'Nama Tamu',
            'Nomor Kontak',
            'Negara',
            'Sumber',
            'Status',
            'Alasan Kalah / Ditolak',
            'Tanggal Tindak Lanjut',
        ]);

        foreach ($leads as $lead) {
            $this->row($handle, [
                $lead->id,
                $lead->created_at?->format('Y-m-d H:i') ?? '-',
                $lead->branch?->name ?? "Cabang #{$lead->branch_id}",
                $lead->name,
                $lead->phone,
                $lead->country ?? '-',
                $lead->source?->value ?? (string) $lead->source,
                $lead->status?->value ?? (string) $lead->status,
                $lead->lost_reason ?? '-',
                $lead->follow_up_at?->format('Y-m-d H:i') ?? '-',
            ]);
        }
    }

    private function writeBookings($handle, iterable $bookings): void
    {
        fwrite($handle, "\xEF\xBB\xBF");

        $this->row($handle, [
            'Kode Pemesanan',
            'Cabang',
            'Nama Tamu',
            'Paket Wisata',
            'Tanggal Berangkat',
            'Tanggal Pulang',
            'Total Tagihan',
            'Mata Uang',
            'Total Terbayar (IDR Eq)',
            'Status',
        ]);

        if ($bookings instanceof Collection) {
            $bookings->loadMissing(['branch', 'quotation.lead', 'quotation.package', 'payments']);
        }

        foreach ($bookings as $b) {
            $verifiedPaid = (int) $b->payments->where('status', 'verified')->sum('idr_equivalent_minor');

            $this->row($handle, [
                $b->code,
                $b->branch?->name ?? "Cabang #{$b->branch_id}",
                $b->quotation?->lead?->name ?? '-',
                $b->quotation?->package?->name ?? '-',
                $b->departure_date ? $b->departure_date->toDateString() : '-',
                $b->return_date ? $b->return_date->toDateString() : '-',
                $b->total_minor,
                $b->currency ?? 'IDR',
                $verifiedPaid,
                $b->status?->value ?? (string) $b->status,
            ]);
        }
    }

    private function toString(callable $write): string
    {
        $handle = fopen('php://temp', 'r+');
        $write($handle);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    private function toOutput(callable $write): void
    {
        $handle = fopen('php://output', 'w');
        $write($handle);
        fclose($handle);
    }

    /**
     * fputcsv with formula-injection guard: a cell starting with = + - @
     * (e.g. a lead name typed on the public form) is prefixed with ' so
     * Excel shows it as text instead of executing it.
     */
    private function row($handle, array $cells): void
    {
        fputcsv($handle, array_map(
            fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v,
            $cells,
        ));
    }
}
