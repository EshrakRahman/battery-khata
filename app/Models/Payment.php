<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPayment
 */
#[Fillable([
    'customer_id',
    'cash_register_session_id',
    'scrap_collection_id',
    'payment_date',
    'payment_method',
    'total_amount',
    'service_charge',
    'reference_no',
    'notes',
    'received_by',
])]
class Payment extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'payment_method' => 'App\Enums\PaymentMethod',
            'total_amount' => 'decimal:2',
            'service_charge' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'payment_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
