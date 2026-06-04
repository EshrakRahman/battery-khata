<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id',
    'product_id',
    'battery_serial_id',
    'sale_price',
    'warranty_months',
])]
class InvoiceItem extends Model
{
    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'warranty_months' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(BatterySerial::class, 'battery_serial_id');
    }
}
