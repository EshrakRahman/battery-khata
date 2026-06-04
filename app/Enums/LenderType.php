<?php

namespace App\Enums;

enum LenderType: string
{
    case Supplier = 'supplier';
    case Customer = 'customer';
    case Staff = 'staff';
    case External = 'external';
}
