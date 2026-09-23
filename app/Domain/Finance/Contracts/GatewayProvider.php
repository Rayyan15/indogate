<?php

namespace App\Domain\Finance\Contracts;

use App\Domain\Finance\Models\PaymentIntent;

/** A provider where the customer pays online on a hosted checkout page. */
interface GatewayProvider extends PaymentProviderInterface
{
    public function checkoutUrl(PaymentIntent $intent): string;
}
