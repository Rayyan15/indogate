<?php

namespace App\Notifications;

class OnlinePaymentReceived extends StaffNotification
{
    public const TYPE = 'online_payment_received';

    public const SEVERITY = 'success';
}
