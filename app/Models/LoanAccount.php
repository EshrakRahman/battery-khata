<?php

namespace App\Models;

use App\Enums\LenderType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperLoanAccount
 */
#[Fillable([
    'lender_type',
    'lender_reference_id',
    'loan_name',
    'principal_amount',
    'outstanding_balance',
    'start_date',
    'notes',
])]
class LoanAccount extends Model
{
    protected function casts(): array
    {
        return [
            'lender_type' => LenderType::class,
            'principal_amount' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'start_date' => 'date',
        ];
    }
}
