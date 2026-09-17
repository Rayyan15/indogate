<?php

namespace App\Enums;

enum InventoryItemType: string
{
    case ROOM = 'room';
    case VEHICLE = 'vehicle';
    case TICKET = 'ticket';
    case ACTIVITY = 'activity';
}
