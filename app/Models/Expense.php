<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperExpense
 */
#[Fillable([
    'expense_category_id',
    'cash_register_session_id',
    'amount',
    'payment_method',
    'expense_date',
    'notes',
    'created_by',
])]
class Expense extends Model
{
    protected static function booted(): void
    {
        static::created(function (Expense $expense) {
            CashbookEntry::create([
                'cash_register_session_id' => $expense->cash_register_session_id,
                'entry_type' => 'Expense',
                'direction' => TransactionDirection::Out,
                'payment_method' => $expense->payment_method,
                'amount' => $expense->amount,
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'notes' => $expense->notes,
                'created_by' => $expense->created_by,
            ]);
        });

        static::updated(function (Expense $expense) {
            CashbookEntry::where('reference_type', Expense::class)
                ->where('reference_id', $expense->id)
                ->update([
                    'cash_register_session_id' => $expense->cash_register_session_id,
                    'payment_method' => $expense->payment_method->value,
                    'amount' => $expense->amount,
                    'notes' => $expense->notes,
                ]);
        });

        static::deleted(function (Expense $expense) {
            CashbookEntry::where('reference_type', Expense::class)
                ->where('reference_id', $expense->id)
                ->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'expense_date' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
