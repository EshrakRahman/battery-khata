<?php

namespace App\Models;

use App\Enums\PdcStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'payment_id',
    'cheque_number',
    'bank_name',
    'amount',
    'maturity_date',
    'deposit_date',
    'cleared_date',
    'bounce_reason',
    'status',
])]
class PostDatedCheque extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'maturity_date' => 'date',
            'deposit_date' => 'date',
            'cleared_date' => 'date',
            'status' => PdcStatus::class,
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
