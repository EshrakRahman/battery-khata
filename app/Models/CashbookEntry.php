<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin IdeHelperCashbookEntry
 */
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
            'direction' => TransactionDirection::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
