<?php

namespace App\Notifications;

class QuotationExpiring extends StaffNotification
{
    public const TYPE = 'quotation_expiring';

    public const SEVERITY = 'warning';
}
