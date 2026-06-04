<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'loan_account_id',
    'transaction_date',
    'transaction_type',
    'amount',
    'notes',
])]
class LoanTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
