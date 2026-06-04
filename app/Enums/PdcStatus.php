<?php

namespace App\Enums;

enum PdcStatus: string
{
    case Pending = 'pending';
    case Deposited = 'deposited';
    case Cleared = 'cleared';
    case Bounced = 'bounced';
}
