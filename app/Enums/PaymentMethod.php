<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Bkash = 'bkash';
    case Nagad = 'nagad';
    case Rocket = 'rocket';
    case Bank = 'bank';
    case Cheque = 'cheque';
    case ScrapAdjustment = 'scrap_adjustment';
}
