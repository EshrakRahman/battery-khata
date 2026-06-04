<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'broker_id',
    'transaction_date',
    'transaction_type',
    'reference_type',
    'reference_id',
    'debit',
    'credit',
    'running_balance',
    'notes',
])]
class BrokerLedger extends Model
{
    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'running_balance' => 'decimal:2',
        ];
    }
}
