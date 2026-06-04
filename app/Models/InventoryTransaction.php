<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperInventoryTransaction
 */
#[Fillable([
    'battery_serial_id',
    'warehouse_id',
    'transaction_type',
    'reference_type',
    'reference_id',
    'notes',
    'created_by',
])]
class InventoryTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'transaction_type' => TransactionType::class,
        ];
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(BatterySerial::class, 'battery_serial_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
