<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'cash_register_session_id',
    'entry_type',
    'direction',
    'payment_method',
    'amount',
    'reference_type',
    'reference_id',
    'notes',
    'created_by',
])]
class CashbookEntry extends Model
{
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
        ];
    }
}
