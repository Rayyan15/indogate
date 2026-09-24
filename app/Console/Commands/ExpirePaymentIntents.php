<?php

namespace App\Console\Commands;

use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Services\GatewayCheckout;
use Illuminate\Console\Command;

class ExpirePaymentIntents extends Command
{
    protected $signature = 'payments:expire-intents';

    protected $description = 'Mark unpaid online payment intents past their deadline as expired';

    public function handle(GatewayCheckout $checkout): int
    {
        $stale = PaymentIntent::withoutGlobalScopes()
            ->where('status', PaymentIntent::STATUS_PENDING)
            ->whereNull('needs_review_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($stale as $intent) {
            // Guarded per-row update: a webhook may have settled the intent since the select.
            $affected = PaymentIntent::withoutGlobalScopes()->whereKey($intent->getKey())
                ->where('status', PaymentIntent::STATUS_PENDING)
                ->whereNull('needs_review_at')
                ->update(['status' => PaymentIntent::STATUS_EXPIRED, 'updated_at' => now()]);

            if ($affected) {
                $count++;
                $checkout->notifyFailed($intent);
            }
        }

        $this->info("Expired {$count} payment intent(s).");

        return self::SUCCESS;
    }
}
