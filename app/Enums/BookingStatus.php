<?php

namespace App\Enums;

enum BookingStatus: string
{
    case DRAFT = 'draft';
    case QUOTED = 'quoted';
    case EXPIRED = 'expired';
    case CONFIRMED = 'confirmed';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
