<?php

namespace App\Enums;

enum TransactionType: string
{
    case Purchase = 'purchase';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Sale = 'sale';
    case WarrantyIn = 'warranty_in';
    case WarrantyOut = 'warranty_out';
    case BufferIssue = 'buffer_issue';
    case BufferReturn = 'buffer_return';
}
