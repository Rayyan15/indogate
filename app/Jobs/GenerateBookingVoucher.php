<?php

namespace App\Jobs;

use App\Domain\Booking\Models\PackageBooking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * PRD M7 step 11: voucher/itinerary generation must go through the queue,
 * same reasoning and pattern as App\Jobs\GeneratePackageItineraryPdf (M5) —
 * no broadcast infra here, readiness is a cache flag a Livewire component
 * polls for.
 */
class GenerateBookingVoucher implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $bookingId,
        public readonly string $locale,
        public readonly int $requestedByUserId,
    ) {}

    public function handle(): void
    {
        $booking = PackageBooking::withoutGlobalScopes()
            ->with(['guests', 'quotation.package.items.inventoryItem'])
            ->findOrFail($this->bookingId);

        app()->setLocale($this->locale);

        $pdf = Pdf::loadView('pdf.booking-voucher', ['booking' => $booking, 'locale' => $this->locale])
            ->setOption('isRemoteEnabled', false)
            ->setOption('isRtl', $this->locale === 'ar');

        $path = "booking-exports/{$booking->id}/voucher-{$this->locale}-".now()->timestamp.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        Cache::put(self::cacheKey($this->bookingId, $this->requestedByUserId, $this->locale), $path, now()->addHour());
    }

    public function failed(\Throwable $e): void
    {
        Cache::put(self::failedKey($this->bookingId, $this->requestedByUserId), true, now()->addMinutes(10));
    }

    public static function failedKey(int $id, int $userId): string
    {
        return 'pdf-export-failed:'.static::class.":{$id}:{$userId}";
    }

    public static function cacheKey(int $bookingId, int $userId, string $locale): string
    {
        return "booking-voucher-ready:{$bookingId}:{$userId}:{$locale}";
    }
}
