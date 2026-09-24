<?php

namespace App\Notifications;

class StorefrontProofUploaded extends StaffNotification
{
    public const TYPE = 'storefront_proof_uploaded';

    public const SEVERITY = 'warning';
}
