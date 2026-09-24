<?php

namespace App\Support\Notifications;

/** Every staff notification type, in display order. */
final class NotificationTypes
{
    public const ALL = [
        'new_website_lead',
        'online_payment_received',
        'online_payment_failed',
        'manual_payment_pending',
        'storefront_proof_uploaded',
        'quotation_expiring',
        'departure_without_driver',
        'lead_follow_up_due',
    ];
}
