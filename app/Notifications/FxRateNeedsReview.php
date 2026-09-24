<?php

namespace App\Notifications;

class FxRateNeedsReview extends StaffNotification
{
    public const TYPE = 'fx_rate_review';

    public const SEVERITY = 'warning';
}
