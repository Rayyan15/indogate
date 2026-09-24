<?php

namespace App\Notifications;

class LeadFollowUpDue extends StaffNotification
{
    public const TYPE = 'lead_follow_up_due';

    public const SEVERITY = 'info';
}
