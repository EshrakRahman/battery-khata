<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperSupplierPayment
 */
#[Fillable([
    'supplier_id',
    'cash_register_session_id',
    'payment_date',
    'payment_method',
    'amount',
    'reference_no',
    'notes',
    'created_by',
])]
class SupplierPayment extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
        ];
    }
}
