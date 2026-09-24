<?php

namespace App\Notifications;

class DepartureWithoutDriver extends StaffNotification
{
    public const TYPE = 'departure_without_driver';

    public const SEVERITY = 'danger';
}
