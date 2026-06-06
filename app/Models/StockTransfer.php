<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperStockTransfer
 */
#[Fillable([
    'source_warehouse_id',
    'destination_warehouse_id',
    'transfer_date',
    'status',
    'notes',
    'created_by',
])]
class StockTransfer extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (StockTransfer $transfer) {
            InventoryTransaction::query()
                ->where('reference_type', StockTransfer::class)
                ->where('reference_id', $transfer->id)
                ->delete();
        });
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
