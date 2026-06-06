<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Services\CashSessionService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperLoanTransaction
 */
#[Fillable([
    'loan_account_id',
    'transaction_date',
    'transaction_type',
    'amount',
    'notes',
])]
class LoanTransaction extends Model
{
    public ?PaymentMethod $payment_method = null;

    protected static function booted(): void
    {
        static::created(function (LoanTransaction $transaction) {
            $loanAccount = $transaction->account;
            if ($loanAccount) {
                if ($transaction->transaction_type === 'Disbursement') {
                    $loanAccount->increment('outstanding_balance', $transaction->amount);
                } elseif ($transaction->transaction_type === 'Repayment') {
                    $loanAccount->decrement('outstanding_balance', $transaction->amount);
                }
            }

            // Log cashbook entry if active session exists
            $session = app(CashSessionService::class)->getActiveSession();
            if ($session) {
                $paymentMethod = $transaction->payment_method ?? PaymentMethod::Cash;
                $entryType = $transaction->transaction_type === 'Disbursement' ? 'LoanReceive' : 'LoanRepayment';
                $direction = $transaction->transaction_type === 'Disbursement' ? TransactionDirection::In : TransactionDirection::Out;

                CashbookEntry::create([
                    'cash_register_session_id' => $session->id,
                    'entry_type' => $entryType,
                    'direction' => $direction,
                    'payment_method' => $paymentMethod,
                    'amount' => $transaction->amount,
                    'reference_type' => LoanTransaction::class,
                    'reference_id' => $transaction->id,
                    'notes' => $transaction->notes,
                    'created_by' => auth()->id() ?? User::first()?->id,
                ]);
            }
        });

        static::deleting(function (LoanTransaction $transaction) {
            $loanAccount = $transaction->account;
            if ($loanAccount) {
                if ($transaction->transaction_type === 'Disbursement') {
                    $loanAccount->decrement('outstanding_balance', $transaction->amount);
                } elseif ($transaction->transaction_type === 'Repayment') {
                    $loanAccount->increment('outstanding_balance', $transaction->amount);
                }
            }

            // Delete cashbook entry
            CashbookEntry::where('reference_type', LoanTransaction::class)
                ->where('reference_id', $transaction->id)
                ->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class, 'loan_account_id');
    }
}
