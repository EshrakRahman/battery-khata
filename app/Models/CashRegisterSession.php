<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperCashRegisterSession
 */
#[Fillable([
    'opened_by',
    'opened_at',
    'closed_at',
    'opening_cash',
    'expected_cash',
    'closing_cash',
    'shortage_excess',
    'denominations',
    'notes',
])]
class CashRegisterSession extends Model
{
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'shortage_excess' => 'decimal:2',
            'denominations' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function cashbookEntries(): HasMany
    {
        return $this->hasMany(CashbookEntry::class);
    }
}
