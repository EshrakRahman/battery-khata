<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperInvoice
 */
#[Fillable([
    'invoice_no',
    'customer_id',
    'broker_id',
    'cash_register_session_id',
    'invoice_date',
    'sub_total',
    'discount_amount',
    'scrap_adjustment',
    'grand_total',
    'invoice_status',
    'notes',
    'created_by',
])]
class Invoice extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'invoice_date' => 'datetime',
            'sub_total' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'scrap_adjustment' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'invoice_status' => InvoiceStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class, 'broker_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }
}
