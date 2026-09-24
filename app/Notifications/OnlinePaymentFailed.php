<?php

namespace App\Notifications;

class OnlinePaymentFailed extends StaffNotification
{
    public const TYPE = 'online_payment_failed';

    public const SEVERITY = 'danger';
}
