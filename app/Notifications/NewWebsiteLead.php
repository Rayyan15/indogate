<?php

namespace App\Notifications;

class NewWebsiteLead extends StaffNotification
{
    public const TYPE = 'new_website_lead';

    public const SEVERITY = 'info';
}
