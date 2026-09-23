<?php

namespace App\Support;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Fx;
use App\Domain\Finance\Services\GatewayCheckout;
use App\Http\Controllers\Public\OnlinePaymentController;

/** Payment link + WhatsApp share URL a CS sends to the customer. */
class PaymentLinkMessage
{
    public static function available(PackageBooking $booking): bool
    {
        $checkout = app(GatewayCheckout::class);

        return $checkout->isEnabled() && $checkout->isPayable($booking);
    }

    public static function whatsappUrl(PackageBooking $booking, ?string $link = null): string
    {
        $link ??= OnlinePaymentController::linkFor($booking);
        $lead = $booking->quotation?->lead;

        $text = __('payment.admin.wa_message', [
            'name' => $lead?->name ?? '',
            'code' => $booking->code,
            'amount' => $booking->currency.' '.Fx::format($booking->remainingBalanceMinor(), $booking->currency),
            'link' => $link,
        ]);

        $phone = preg_replace('/\D+/', '', (string) $lead?->phone);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }
}
