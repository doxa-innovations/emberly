<?php

namespace App\Enums;

enum LedgerType: string
{
    case Purchase = 'purchase';
    case Hold = 'hold';
    case Release = 'release';
    case Charge = 'charge';
}
