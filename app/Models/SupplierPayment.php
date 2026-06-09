<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Services\LedgerService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    protected static function booted(): void
    {
        static::created(function (SupplierPayment $payment) {
            // 1. Record SupplierLedger debit transaction (payout decreases payables)
            app(LedgerService::class)->recordSupplierTransaction(
                $payment->supplier,
                $payment->amount, // debit
                0.00, // credit
                'Payment', // transactionType
                $payment, // reference
                __('Supplier Payment logged #').$payment->id
            );

            // 2. Record CashbookEntry output transaction
            CashbookEntry::create([
                'cash_register_session_id' => $payment->cash_register_session_id,
                'entry_type' => 'SupplierPayment',
                'direction' => TransactionDirection::Out,
                'payment_method' => $payment->payment_method,
                'amount' => $payment->amount,
                'reference_type' => SupplierPayment::class,
                'reference_id' => $payment->id,
                'notes' => $payment->notes,
                'created_by' => $payment->created_by,
            ]);
        });
    }

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
