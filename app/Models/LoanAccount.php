<?php

namespace App\Models;

use App\Enums\LenderType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function transactions(): HasMany
    {
        return $this->hasMany(LoanTransaction::class, 'loan_account_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'lender_reference_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'lender_reference_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lender_reference_id');
    }

    public function getLenderNameAttribute(): string
    {
        return match ($this->lender_type) {
            LenderType::Supplier => $this->supplier?->name ?? __('Unknown Supplier'),
            LenderType::Customer => $this->customer?->name ?? __('Unknown Customer'),
            LenderType::Staff, LenderType::External => $this->user?->name ?? __('Unknown User'),
            default => __('Unknown'),
        };
    }
}
