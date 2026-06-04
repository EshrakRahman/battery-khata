<?php

namespace App\Models;

use App\Enums\BatteryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_id', 'serial_no', 'current_status'])]
class BatterySerial extends Model
{
    protected function casts(): array
    {
        return [
            'current_status' => BatteryStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'battery_serial_id');
    }
}
