<?php

namespace App\Enums;

enum PaymentChannel: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case INTERNATIONAL_CARD = 'international_card';
}
