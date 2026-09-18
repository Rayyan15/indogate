<?php

namespace App\Jobs;

use App\Domain\Packaging\Models\Package;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * PRD M5 step 12: PDF export must go through the queue so the builder page
 * never hangs. This app has no broadcast/Reverb infra (BROADCAST_CONNECTION
 * =log), so readiness is signaled via a cache flag the Livewire component
 * polls for (wire:poll) rather than a websocket event.
 */
class GeneratePackageItineraryPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $packageId,
        public readonly string $locale,
        public readonly int $requestedByUserId,
    ) {}

    public function handle(): void
    {
        $package = Package::withoutGlobalScopes()->with(['days', 'items.inventoryItem'])->findOrFail($this->packageId);

        app()->setLocale($this->locale);

        $pdf = Pdf::loadView('pdf.package-itinerary', ['package' => $package, 'locale' => $this->locale])
            ->setOption('isRemoteEnabled', false)
            ->setOption('isRtl', $this->locale === 'ar');

        $path = "package-exports/{$package->id}/itinerary-{$this->locale}-".now()->timestamp.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        Cache::put(self::cacheKey($this->packageId, $this->requestedByUserId, $this->locale), $path, now()->addHour());
    }

    public static function cacheKey(int $packageId, int $userId, string $locale): string
    {
        return "package-export-ready:{$packageId}:{$userId}:{$locale}";
    }
}
