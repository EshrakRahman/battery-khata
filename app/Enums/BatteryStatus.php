<?php

namespace App\Enums;

enum BatteryStatus: string
{
    case InStock = 'in_stock';
    case Sold = 'sold';
    case Reserved = 'reserved';
    case WarrantyClaim = 'warranty_claim';
    case BufferIssued = 'buffer_issued';
    case SupplierReturned = 'supplier_returned';
    case Scrap = 'scrap';
}
