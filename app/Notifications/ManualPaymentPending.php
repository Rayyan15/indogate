<?php

namespace App\Notifications;

class ManualPaymentPending extends StaffNotification
{
    public const TYPE = 'manual_payment_pending';

    public const SEVERITY = 'warning';
}
