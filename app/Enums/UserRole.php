<?php

namespace App\Enums;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case CS_ADMIN = 'cs_admin';
    case FINANCE_ADMIN = 'finance_admin';
    case SUPER_ADMIN = 'super_admin';
}
