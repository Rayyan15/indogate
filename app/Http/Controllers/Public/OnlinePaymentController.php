<?php

namespace App\Http\Controllers\Public;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Services\GatewayCheckout;
use App\Enums\PaymentChannel;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OnlinePaymentController extends Controller
{
    /** Customer-facing methods; each maps to a PaymentChannel for fee accounting. */
    public const METHODS = [
        'va_bca' => PaymentChannel::BANK_TRANSFER,
        'va_mandiri' => PaymentChannel::BANK_TRANSFER,
        'qris' => PaymentChannel::BANK_TRANSFER,
        'card' => PaymentChannel::INTERNATIONAL_CARD,
    ];

    public function __construct(private GatewayCheckout $checkout) {}

    /** Link a CS sends to the customer (signed, expires). */
    public static function linkFor(PackageBooking $booking): string
    {
        $supported = array_keys(config('laravellocalization.supportedLocales'));
        $locale = $booking->quotation?->lead?->locale;

        // Open in the customer's language, not the staff member's.
        return URL::temporarySignedRoute('payments.booking', now()->addDays(config('payments.link_ttl_days')), [
            'locale' => in_array($locale, $supported, true) ? $locale : app()->getLocale(),
            'packageBooking' => $booking->id,
        ]);
    }

    public function booking(PackageBooking $packageBooking): View
    {
        abort_unless($this->checkout->isEnabled(), 404);
        $booking = $packageBooking->load('quotation.lead', 'quotation.package');

        return view('public.payment.booking', [
            'booking' => $booking,
            'payable' => $this->checkout->isPayable($booking),
            'remaining' => $booking->remainingBalanceMinor(),
            'dpAmount' => $booking->status->value === 'confirmed' ? $this->checkout->amountFor($booking, Payment::TYPE_DOWN_PAYMENT) : null,
            'dpPercent' => config('payments.down_payment_percent'),
            'methods' => array_keys(self::METHODS),
            'postUrl' => request()->fullUrl(),
        ]);
    }

    public function start(Request $request, PackageBooking $packageBooking): RedirectResponse
    {
        abort_unless($this->checkout->isEnabled(), 404);

        $data = $request->validate([
            'type' => ['required', 'in:'.Payment::TYPE_DOWN_PAYMENT.','.Payment::TYPE_FULL_PAYMENT],
            'method' => ['required', 'in:'.implode(',', array_keys(self::METHODS))],
        ]);

        try {
            $intent = $this->checkout->startCheckout($packageBooking, $data['type'], self::METHODS[$data['method']]->value, $data['method']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()->away($this->checkout->provider()->checkoutUrl($intent));
    }

    /** Hosted checkout page of the simulator (a real gateway hosts this itself). */
    public function simulator(PaymentIntent $intent): View|RedirectResponse
    {
        abort_unless($intent->provider === 'simulator', 404);

        if (! $intent->isPayable()) {
            return redirect()->route('payments.result', ['intent' => $intent->public_token]);
        }

        return view('public.payment.checkout', [
            'intent' => $intent->load('booking.quotation.lead'),
        ]);
    }

    /** Simulator buttons: fire the same signed webhook a real gateway would send. */
    public function simulate(Request $request, PaymentIntent $intent): RedirectResponse
    {
        abort_unless($intent->provider === 'simulator', 404);
        $outcome = $request->validate(['outcome' => ['required', 'in:paid,failed,expired']])['outcome'];

        $body = json_encode([
            'event_id' => 'sim_'.Str::ulid(),
            'type' => 'payment.'.$outcome,
            'intent' => $intent->public_token,
            'reference' => 'SIM-'.strtoupper(Str::random(10)),
            'reason' => $outcome === 'failed' ? 'Ditolak oleh bank penerbit (simulasi)' : null,
        ]);

        // Called in-process: `php artisan serve` is single-threaded, an HTTP call to ourselves would deadlock.
        $this->checkout->handleWebhook('simulator', $body, GatewayCheckout::sign($body));

        return redirect()->route('payments.result', ['intent' => $intent->public_token]);
    }

    public function result(PaymentIntent $intent): View
    {
        $intent->refresh()->load('booking');

        return view('public.payment.result', [
            'intent' => $intent,
            'booking' => $intent->booking,
            'retryUrl' => $intent->booking ? self::linkFor($intent->booking) : null,
        ]);
    }

    public function webhook(Request $request, string $provider): JsonResponse
    {
        try {
            $outcome = $this->checkout->handleWebhook($provider, $request->getContent(), $request->header('X-Signature'));
        } catch (InvalidArgumentException $e) {
            report($e);

            return response()->json(['error' => $e->getMessage()], 401);
        }

        return response()->json(['status' => $outcome]);
    }
}
