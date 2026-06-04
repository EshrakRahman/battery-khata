<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Received = 'received';
    case SentToSupplier = 'sent_to_supplier';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Resolved = 'resolved';
}
